<?php
/*
 * Copyright 2016 Stormpath, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Stormpath\Lumen\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StormpathConfigCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'stormpath:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Configuration file to make modifications';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $config = dirname(__DIR__) . '/config/stormpath.yaml';

        copy($config, $path = base_path('/stormpath.yaml'));

        $this->info('Stormpath Configuration has been moved to ' . $path);
    }
}