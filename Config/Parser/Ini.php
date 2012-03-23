<?php

namespace Serenity\Config\Parser;

class Ini
{
    public function __invoke($contents)
    {
        $data = \parse_ini_string((string) $contents, true);
        if ($data === false) {
            return false;
        }

        $result = new \stdClass();
        foreach ($data as $section => $sectionData) {
            // Inherit parent section(s).
            $parents = \explode(':', $section);
            $section = \trim(\array_shift($parents));
            foreach ($parents as $parent) {
                $data[$section] = $sectionData += $data[\trim($parent)];
            }

            $result->$section = new \stdClass();
            foreach ($sectionData as $key => $value) {
                $parts = \explode('.', $key);
                $partsNum = \count($parts);
                $pointer = &$result->$section;

                foreach ($parts as $index => $part) {
                    if ($index == $partsNum - 1) {
                        $pointer->$part = $value;
                    } else {
                        if (!\property_exists($pointer, $part)) {
                            $pointer->$part = new \stdClass();
                        }

                        $pointer = &$pointer->$part;
                    }
                }
            }
        }

        return $result;
    }
}
