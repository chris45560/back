<?php

namespace Drupal\idix_back\Controller;

use Drupal\Core\Controller\ControllerBase;

class IdixbackModuleController extends ControllerBase
{
    public function films_page()
    {
        return [
            '#theme' => 'idix_back_films_page_theme',
        ];
    }
}
