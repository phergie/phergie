<?php

require_once 'phing/tasks/ext/PearPackage2Task.php';

class PhergiePackageTask extends PearPackage2Task
{
    protected function setOptions()
    {
        $this->pkg->addMaintainer('lead', 'team', 'Phergie Development Team', 'team@phergie.org');

        $path = str_replace('_', '/', $this->package) . '.php'; 
        if (file_exists($path)) {
            $contents = file_get_contents($path);
            preg_match_all('#/\*\*(.*)\*/#Ums', $contents, $matches, PREG_SET_ORDER);
            $doc = $matches[1][1];

            $have_summary = false;
            $have_description = false;
            foreach ($this->options as $option) {
                switch ($option->getName()) {
                    case 'summary':
                        $have_summary = true;
                        break;
                    case 'description':
                        $have_descripion = true;
                        break;
                }
            }

            if (!$have_summary || !$have_description) {
                $description = substr($doc, 0, strpos($doc, '@'));
                $description = trim(preg_replace(array('#^[\h*]*|[\h*]*$#m', '#[\h]+#m'), array('', ' '), $description));
                $split = preg_split('/\v\v+/', $description);
                $summary = trim(array_shift($split));
                if (!$have_summary) {
                    $this->pkg->setSummary(htmlentities($summary, ENT_QUOTES));
                }
                if (!$have_description) {
                    $this->pkg->setDescription(htmlentities($description, ENT_QUOTES));
                }
            }

            $doc = preg_split('/\v+/', $doc);
            $doc = preg_grep('/@uses/', $doc);
            $doc = preg_replace('/\s*\* @uses\s+|\s+$/', '', $doc);
            foreach ($doc as $line) {
                if (strpos($line, 'extension') === 0) {
                    $line = explode(' ', $line);
                    $name = $line[1];
                    $optional = 'required';
                    if (isset($line[2])) {
                        $optional = $line[2];
                    }
                    $this->pkg->addExtensionDep(
                        $optional,
                        $name
                    );
                } else {
                    $line = explode(' ', $line);
                    $name = $line[0];
                    $channel = $line[1];
                    $optional = 'required';
                    if (isset($line[2])) {
                        $optional = $line[2];
                    }
                    $this->pkg->addPackageDepWithChannel(
                        $optional,
                        $name,
                        $channel
                    );
                }
            }
        }

        $newmap = array();
        foreach ($this->mappings as $key => $map) {
            switch ($map->getName()) {
                case 'releases':
                    $releases = $map->getValue();
                    foreach ($releases as $release) {
                        $this->pkg->addRelease();
                        if (isset($release['installconditions'])) {
                            if (isset($release['installconditions']['os'])) {
                                $this->pkg->setOsInstallCondition($release['installconditions']['os']);
                            }
                        }
                        if (isset($release['filelist'])) {
                            if (isset($release['filelist']['install'])) {
                                foreach ($release['filelist']['install'] as $file => $as) {
                                    $this->pkg->addInstallAs($file, $as);
                                }
                            }
                            if (isset($release['filelist']['ignore'])) {
                                foreach ($release['filelist']['ignore'] as $file) {
                                    $this->pkg->addIgnoreToRelease($file);
                                }
                            }
                        }
                    }
                    break;

                default:
                    $newmap[] = $map;
            }
        }
        $this->mappings = $newmap;

        parent::setOptions();
    }
}
