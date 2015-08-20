<?php

namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Settings\Settings;

class SettingsController {

    private $templatesController = null;
    private $route = null;
    private $settings = null;

    private $indexTemplate = null;
    private $groupsTemplate = null;
    private $settingsTemplate = null;

    public function __construct ($templatesController, $route) {
        $this->templatesController = $templatesController;
        $this->route = $route;
    }

    public function buildPage () {
        if (isset($this->route->vars->action)) {
            $out = array('json', json_encode($this->saveSettings()));
        } else {
            $this->settings = Settings::setSettings();
            $this->getTemplates();
            $groups = $this->buildGroups();

            $replacements = array(
                'groups' => $groups,
            );
            $out = TemplatesController::replaceInTemplate($this->indexTemplate, $replacements);
        }

        return $out;
    }

    private function getTemplates () {
        $this->indexTemplate = $this->templatesController->getTemplate('Settings/Index');
        $this->groupsTemplate = $this->templatesController->getTemplate('Settings/Groups');
        $this->settingsTemplate = $this->templatesController->getTemplate('Settings/Settings');
    }

    private function buildGroups () {
        $out = '';
        foreach ($this->settings as $type => $settingGroup) {
            $replacements = array (
                'head' => $settingGroup->title,
                'type' => $type,
                'rows' => $this->buildSettings($settingGroup->settings, $type),
            );
            $out .= TemplatesController::replaceInTemplate($this->groupsTemplate, $replacements);
        }

        return $out;
    }

    private function buildSettings ($settings, $type) {
        $out = '';
        foreach ($settings as $setting) {
            switch ($type) {
                case 'bool':
                    $valTrue = ($setting->value == 'true') ? ' selected' : '';
                    $valFalse = ($setting->value == 'false') ? ' selected' : '';

                    $val = "<select id=\"$setting->name\" class=\"$type\">";
                    $val .= "    <option value=\"true\"$valTrue>True</option>";
                    $val .= "    <option value=\"false\"$valFalse>False</option>";
                    $val .= '</select>';
                    break;
                default:
                    $val = $setting->value;
            }

            $replacements = array (
                'info' => $setting->info,
                'val' => $val,
            );
            $out .= TemplatesController::replaceInTemplate($this->settingsTemplate, $replacements);
        }
        return $out;
    }

    private function saveSettings () {
        return Settings::saveSettings($_REQUEST);
    }
}