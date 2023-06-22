<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.db.DBResultRange');
import('classes.core.Services');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class DonateButtonPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = NULL)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                HookRegistry::register('Templates::Article::Main', array($this, 'addButton'));
                HookRegistry::register('TemplateManager::display', array($this, 'checkURL'));
                $this->modifyDatabase();
            }
            return true;
        }
        return false;
    }


    public function getDisplayName()
    {
        return 'Donate Button Plugin';
    }

    public function getDescription()
    {
        return 'Enable a donate button';
    }

    public function isPluginEnabled()
    {
        return $this->getEnabled();
    }


    public function addButton($hookName, $args)
    {
        $smarty = &$args[1];
        $output = &$args[2];

        $article = $smarty->getTemplateVars('article');

        if ($article && $article->getStatus() === STATUS_PUBLISHED) {
            $output .= $smarty->fetch($this->getTemplateResource('donate_button.tpl'));
        }
    }

    private function modifyDatabase()
    {
        try {
            $schema = Capsule::schema();

            $newFields = array(
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'metamask_address'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'contract_address'
                ),
            );

            foreach ($newFields as $field) {
                $tableName = $field['tableName'];
                $fieldName = $field['fieldName'];

                if ($this->checkColumnInDB($tableName, $fieldName)) {
                    $schema->table($tableName, function ($table) use ($fieldName) {
                        $table->string($fieldName)->nullable();
                    });
                }
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    private function checkColumnInDB($tableName, $fieldName)
    {
        try {
            $conn = Capsule::connection();

            $tableColumns = $conn->getDoctrineSchemaManager()->listTableColumns($tableName);

            // Check if the field already exists in the table
            if (!array_key_exists($fieldName, $tableColumns)) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    public function checkURL($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $currentUrl = $request->url();
        $templateMgr = TemplateManager::getManager($request);

        if (strpos($currentUrl, '/submission/wizard') !== false) {
            // Add the script file using TemplateManager
            $templateMgr->addJavaScript(
                'helloWorld',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/showAgreement.js?v='.time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
        } else {
            error_log('Current URL does not contain "/submission/wizard".');
        }
    }
}
