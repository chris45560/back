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
        /***** Champs 'name' *****/
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Name'),
            '#required' => TRUE,
        );

        /** On récupère tous les 'nids' de type 'idix_back_film' **/
        $nids = db_select('node', 'n')
            ->fields('n', array('nid'))
            ->condition('type', 'idix_back_film', '=')
            ->execute()
            ->fetchCol();

        /** On récupère toutes les données des noeuds correspodants aux 'nids' précédemment récupérés **/
        $nodes = node_load_multiple($nids);

        /** On récupère les noms des films du type de contenu 'idix_back_film' **/
        $titles = [];
        foreach ($nodes as $key => $value) {
            $titles[$key] = $value->title->value;
        }

        /***** Champs 'films' *****/
        $form['films'] = [
            '#type' => 'checkboxes',
            '#title' => 'Films',
            '#description' => '',
            '#options' => $titles,
        ];

        /***** Champs 'submit' *****/
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

        /** Création d'un nouveau noeud de type 'idix_back_personnage' **/
        $my_article = Node::create(['type' => 'idix_back_personnage']);

        /** On lui attribue la valeur du champ 'name' précédemment rentrée dans le formulaire **/
        $my_article->set('title', $form_state->getValue('name'));

        /** Stockage des films précédemment cochés dans la variable '$films' **/
        $films = [];
        foreach ($form_state->getValue('films') as $key => $value) {
            if (!empty($value)) {
                $films[] = $form_state->getValue('films')[$key] = $value;
            }
        }

        /** On lui attribue la valeur du champ 'name' précédemment rentrée dans le formulaire **/
        $my_article->set('field_idix_back_personnage_name', $form_state->getValue('name'));
        $my_article->set('field_idix_back_personnage_films', $films);

        /** Enregistrement du nouveau noeud **/
        $my_article->enforceIsNew();
        $my_article->save();

        /** On récupère tous les 'nids' de type 'idix_back_personnage' **/
        $nids = db_select('node', 'n')
            ->fields('n', array('nid'))
            ->condition('type', 'idix_back_personnage', '=')
            ->execute()
            ->fetchCol();

        /** On récupère la valeur du dernier 'nid' créé de type 'idix_back_personnage' **/
        $new_nid = end($nids);

        /** Modification des personnages dans les films déjà présents **/
        foreach ($films as $key => $value) {
            /** On charge les films 1 par 1 précédemment cochés dans le formulaire **/
            $node = \Drupal\node\Entity\Node::load($value);
            $array = $node->toArray();
            $characters = [];

            /** S'il y a déjà des personnages présents dans le film **/
            if (!empty($array['field_idix_back_film_personnages'])) {
                $actualscharacters = $array['field_idix_back_film_personnages'];

                foreach ($actualscharacters as $keys => $values) {
                    $characters[] = $values['target_id'];
                }

                /** On rajoute l'id du personnage nouvellement créé parmis ceux déjà présents **/
                array_push($characters, $new_nid);

                /** Mise à jour du noeud **/
                $node->set('field_idix_back_film_personnages', $characters);
                $node->save();

                /** S'il n'y a encore aucun personnage présent dans le film **/
            } else {
                /** On relie l'id du personnage nouvellement créé au film **/
                /** Mise à jour du noeud **/
                $node->set('field_idix_back_film_personnages', $new_nid);
                $node->save();
            }
        }

        /** Redirection vers la page du personnage nouvellement créé **/
        $path = Url::fromUserInput('/node/' . $new_nid)->toString();
        $response = new RedirectResponse($path);
        $response->send();
    }
}
