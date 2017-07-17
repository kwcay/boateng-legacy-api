<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Architecture062 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Languages.
        Schema::create('languages', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('code', 7)->unique();
            $table->string('parent_code', 7)->nullable()->index();
            $table->string('name', 200);
            $table->string('alt_names', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Alpabets.
        Schema::create('alphabets', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('script_code', 4)->nullable();
            $table->text('letters')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Language families.
        Schema::create('language_families', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('name', 400);
        });
        DB::statement('CREATE FULLTEXT INDEX idx_name ON language_families (name)');

        // Related languages.
        Schema::create('related_languages', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('language_1_id')->unsigned();
            $table->foreign('language_1_id')
                ->references('id')
                ->on('languages')
                ->onDelete('cascade');

            $table->integer('language_2_id')->unsigned();
            $table->foreign('language_2_id')
                ->references('id')
                ->on('languages')
                ->onDelete('cascade');

            $table->tinyInteger('relation')->unsigned();
            $table->tinyInteger('intelligibility')->unsigned()->nullable();
        });

        // Cultures.
        Schema::create('cultures', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('language_id')->unsigned()->nullable();
            $table->foreign('language_id')
                ->references('id')
                ->on('languages');

            $table->string('name', 400);
            $table->string('alt_names', 400);
            $table->timestamps();
            $table->softDeletes();
        });

        // Definitions.
        Schema::create('definitions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->tinyInteger('type')->unsigned();
            $table->string('sub_type', 10);
            $table->string('main_language_code', 7);            // @deprecated
            $table->json('related_definitions')->nullable();
            $table->tinyInteger('rating')->unsigned();
            $table->text('meta');
            $table->timestamps();
            $table->softDeletes();
        });

        // Definition titles.
        Schema::create('definition_titles', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('definition_id')->unsigned();
            $table->foreign('definition_id')
                ->references('id')
                ->on('definitions')
                ->onDelete('cascade');

            $table->integer('alphabet_id')->unsigned()->nullable();
            $table->foreign('alphabet_id')
                ->references('id')
                ->on('alphabets')
                ->onDelete('cascade');

            $table->string('title', 400);
            $table->timestamps();
            $table->softDeletes();
        });

        // Translations.
        Schema::create('translations', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('definition_id')->unsigned();
            $table->foreign('definition_id')
                ->references('id')
                ->on('definitions')
                ->onDelete('cascade');

            $table->string('language', 3)->index(); // Main language (not expecting sub-languages here).
            $table->text('practical');              // Actual translation.
            $table->text('literal')->nullable();    // Literal translation.
            $table->text('meaning')->nullable();    // Elaboration on the meaning of the definition.
            $table->timestamps();
            $table->softDeletes();
        });
        DB::statement('CREATE FULLTEXT INDEX idx_practical ON translations (practical)');
        DB::statement('CREATE FULLTEXT INDEX idx_literal ON translations (literal)');
        DB::statement('CREATE FULLTEXT INDEX idx_meaning ON translations (meaning)');

        // Related definitions.
        // TODO: is this deprecated? (2017-03-01)
        Schema::create('related_definitions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('definition_id')->unsigned();
            $table->foreign('definition_id')
                ->references('id')
                ->on('definitions')
                ->onDelete('cascade');

            $table->integer('related_id')->unsigned();
            $table->foreign('related_id')
                ->references('id')
                ->on('definitions')
                ->onDelete('cascade');

            $table->integer('relation')->unsigned();
        });

        // Tags.
        Schema::create('tags', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('title', 150)->unique();
            $table->string('language', 3);
            $table->softDeletes();
        });
        DB::statement('CREATE FULLTEXT INDEX idx_title ON tags (title)');

        // Descriptions and other textual data.
        Schema::create('data', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('parent_id')->unsigned();
            $table->string('parent_type');
            $table->text('content');
            $table->string('language', 3);
            $table->timestamps();
            $table->softDeletes();
        });
        DB::statement('CREATE FULLTEXT INDEX idx_content ON data (content)');

        // Media.
        Schema::create('media', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('parent_id')->unsigned();
            $table->string('parent_type');
            $table->string('mime_type');
            $table->string('disk', 40);
            $table->string('path');
            $table->timestamps();
            $table->softDeletes();
        });

        // References.
        Schema::create('references', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('type', 20);
            $table->json('data');
            $table->string('string', 400);
            $table->softDeletes();
        });
        DB::statement('CREATE FULLTEXT INDEX idx_reference ON `references` (string)');

        // References pivot table.
        Schema::create('referenceable', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->integer('reference_id')->unsigned();
            $table->foreign('reference_id')
                ->references('id')
                ->on('references')
                ->onDelete('cascade');

            $table->integer('referenceable_id')->unsigned();
            $table->string('referenceable_type');
        });

        // Transliterations.
        Schema::create('transliterations', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('parent_id')->unsigned();
            $table->string('parent_type');
            $table->string('language', 3);
            $table->string('transliteration', 400);
            $table->timestamps();
            $table->softDeletes();
        });
        DB::statement('CREATE FULLTEXT INDEX idx_transliteration ON transliterations (transliteration)');

        // Geographical areas.
        Schema::create('areas', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('culture_id')->unsigned();
            $table->string('name');
            $table->text('data');
        });

        // Contributors pivot table.
        Schema::create('data_user', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->integer('data_id')->unsigned();
            $table->foreign('data_id')
                ->references('id')
                ->on('data')
                ->onDelete('cascade');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->integer('contribution_score')->unsigned();
        });

        // Pivot tables.
        $pivots = [
            'alphabet_language',
            'cultures_areas',
            'definition_language',
            'definition_tag',
        ];

        foreach ($pivots as $pivot) {
            Schema::create($pivot, function (Blueprint $table) use ($pivot) {
                list($table1, $table2) = explode('_', $pivot);

                $table->engine = 'InnoDB';
                $table->integer($table1.'_id')->unsigned();
                $table->integer($table2.'_id')->unsigned();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop everything except for the "user" and "password_resets" tables.
        Schema::hasTable('language_families') ? DB::statement('ALTER TABLE language_families DROP INDEX idx_name') : null;
        Schema::hasTable('translations') ? DB::statement('ALTER TABLE translations DROP INDEX idx_practical') : null;
        Schema::hasTable('translations') ? DB::statement('ALTER TABLE translations DROP INDEX idx_literal') : null;
        Schema::hasTable('translations') ? DB::statement('ALTER TABLE translations DROP INDEX idx_meaning') : null;
        Schema::hasTable('tags') ? DB::statement('ALTER TABLE tags DROP INDEX idx_title') : null;
        Schema::hasTable('data') ? DB::statement('ALTER TABLE data DROP INDEX idx_content') : null;
        Schema::hasTable('references') ? DB::statement('ALTER TABLE `references` DROP INDEX idx_reference') : null;
        Schema::hasTable('transliterations') ? DB::statement('ALTER TABLE transliterations DROP INDEX idx_transliteration') : null;
        $drop = [
            'definition_tag', 'definition_language', 'cultures_areas',
            'alphabet_language', 'data_user',

            'areas', 'transliterations', 'referenceable', 'references', 'media', 'data', 'tags',
            'related_definitions', 'translations', 'definition_titles', 'definitions', 'cultures',
            'related_languages', 'language_families', 'alphabets', 'languages',
        ];

        foreach ($drop as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
}
