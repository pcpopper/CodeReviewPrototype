<?php


namespace CodeReviewPrototype\App\Settings;


use CodeReviewPrototype\App\Models\SettingsModel;

class Settings {
    const TESTING = true;

    const BRANCH_PARENTS = 'enhancement>develop hotfix>master fix>master feature>develop';

    const ROOT_PATH = '/Users/pcpopper/Sites/CodeReviewPrototype/public';
    const DICTIONARY_PATH = '../App/Settings/dictionary';
    const ERROR_FILE_PATH = '../App/Settings/errorFilled';
    const SETTINGS_FILE_PATH = '../App/Settings/settings';

    public static function setSettings () {
        $settingsRaw = self::getSettings();

        return (object) array (
            'bool' => (object) array(
                'title' => 'On/Off Settings',
                'settings' => array(
                    new SettingsModel('bracketPlacement', 'Allow brackets to be on next line', self::makeSetting($settingsRaw, 'bracketPlacement', 'true')),
                    new SettingsModel('phpVariableVariables', 'Allow the use of variable variables in php', self::makeSetting($settingsRaw, 'phpVariableVariables', 'false')),
                ),
            ),
            'group2' => (object) array(
                'title' => 'Group 2',
                'settings' => array(
                    new SettingsModel('var1', 'Setting 1', self::makeSetting($settingsRaw, 'var1', 'true')),
                ),
            ),
        );
    }

    private static function getSettings () {
        $filename = self::SETTINGS_FILE_PATH;
        if (file_exists($filename) && filesize($filename) > 0) {
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);

            $lines = explode("\n", $contents);
            array_pop($lines);
            $out = array();
            foreach ($lines as $line) {
                $vars = explode(',', $line);
                $out[$vars[0]] = $vars[1];
            }

            return (object) $out;
        }
    }

    private static function makeSetting ($settingsRaw, $name, $default) {
        return isset($settingsRaw->$name) ? $settingsRaw->$name : $default;
    }

    public static function saveSettings ($request) {
        $filename = self::SETTINGS_FILE_PATH;
        $lines = array();
        $section = $request['section'];
        if (file_exists($filename) && filesize($filename)) {
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);

            $contents =  preg_replace("/$section.+\n/", "", $contents);
            $lines = explode("\n", $contents);
            array_pop($lines);
        }

        array_shift($request);

        foreach ($request as $var => $value) {
            $lines[] = "$section,$var,$value";
        }

        $handle = fopen($filename, "w");
        fwrite($handle, implode("\n", $lines));
        fclose($handle);

        return array(1);
    }
}