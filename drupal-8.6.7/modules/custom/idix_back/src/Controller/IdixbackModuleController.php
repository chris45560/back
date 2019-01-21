<?php

namespace Drupal\idix_back\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

class IdixbackModuleController extends ControllerBase
{
    public function films_page()
    {
        return [
            '#theme' => 'idix_back_films_page_theme',
        ];
    }

    public function films_id(NodeInterface $node)
    {
        /** On récupère l'id du node passé en paramètre dans l'Url **/
        $node_id = $node->id();

        /** On récupère tous les 'nids' de type 'idix_back_film' **/
        $nids = db_select('node', 'n')
            ->fields('n', array('nid'))
            ->condition('type', 'idix_back_film', '=')
            ->execute()
            ->fetchCol();

        $film = [
            'title' => $node->get('field_idix_back_film_title')->getValue()[0]['value'],
            'abstract' => $node->get('field_idix_back_film_abstract')->getValue()[0]['value'],
            'date' => $node->get('field_idix_back_film_date')->getValue()[0]['value'],
            'personnages' => []
        ];

        $characters = [];
        $characters_table = $node->get('field_idix_back_film_personnages')->getValue();
        $id_perso_table = [];

        foreach ($characters_table as $key => $value) {

            $id_perso = $value['target_id'];
            $node = \Drupal\node\Entity\Node::load($id_perso);
            $array = $node->toArray();
            $characters = $array['field_idix_back_personnage_name'];
            $id_perso_table[$id_perso] = $characters[0]['value'];
        }

        $film['personnages'] = $id_perso_table;

        /** On vérifie si l'id du node passé en paramètre dans l'Url est un node de type 'idix_back_film' */
        if (in_array($node_id, $nids)) {
            return [
                '#theme' => 'idix_back_films_id_theme',
                '#nid' => $node_id,
                '#film_detail' => $film
            ];
        } else {
            return false;
        }
    }
}
