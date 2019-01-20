<?php

namespace Drupal\idix_back\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

use \Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements a form.
 */
class IdixbackForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'idix_back_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Name'),
            '#required' => TRUE,
        );

        $nids = db_select('node', 'n')
            ->fields('n', array('nid'))
            ->condition('type', 'idix_back_film', '=')
            ->execute()
            ->fetchCol();

        // Get all of the article nodes.
        $nodes = node_load_multiple($nids);

        $titles = [];
        foreach ($nodes as $key => $value) {
            $titles[$key] = $value->title->value;
        }

        $form['films'] = [
            '#type' => 'checkboxes',
            '#title' => 'Films',
            '#description' => '',
            '#options' => $titles,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('name') == '') {
            $form_state->setErrorByName('name', $this->t('Le nom est requis !'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        drupal_set_message($this->t('@number a été ajouté avec succés !', ['@number' => $form_state->getValue('name')]));

        $my_article = Node::create(['type' => 'idix_back_personnage']);
        $my_article->set('title', $form_state->getValue('name'));

        $films = [];
        foreach ($form_state->getValue('films') as $key => $value) {
            if (!empty($value)) {
                $films[] = $form_state->getValue('films')[$key] = $value;
            }
        }

        $my_article->set('field_idix_back_personnage_name', $form_state->getValue('name'));
        $my_article->set('field_idix_back_personnage_films', $films);

        $my_article->enforceIsNew();
        $my_article->save();

        $nids = db_select('node', 'n')
            ->fields('n', array('nid'))
            ->condition('type', 'idix_back_personnage', '=')
            ->execute()
            ->fetchCol();

        $new_nid = end($nids);

        foreach ($films as $key => $value) {
            $node = \Drupal\node\Entity\Node::load($value);
            $array = $node->toArray();
            $characters = [];

            if (!empty($array['field_idix_back_film_personnages'])) {
                $actualscharacters = $array['field_idix_back_film_personnages'];

                foreach ($actualscharacters as $keys => $values) {
                    $characters[] = $values['target_id'];
                }
                array_push($characters, $new_nid);

                $node->set('field_idix_back_film_personnages', $characters);
                $node->save();
            } else {
                $node->set('field_idix_back_film_personnages', $new_nid);
                $node->save();
            }
        }
        $path = Url::fromUserInput('/node/' . $new_nid)->toString();
        $response = new RedirectResponse($path);
        $response->send();
    }
}
