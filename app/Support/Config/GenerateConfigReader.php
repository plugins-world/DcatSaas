<?php

namespace Plugins\DcatSaas\Support\Config;

class GenerateConfigReader
{
    public static function read(string $value): GeneratorPath
    {
        return new GeneratorPath(config("dcat-saas.paths.generator.$value"));
    }
}
