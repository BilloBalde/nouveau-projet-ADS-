<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('placeName');
            $table->string('countryName');
            $table->string('description');
            $table->timestamps();
        });

        DB::table('places')->insert([
            [
                'placeName' => 'Conakry',
                'countryName' => 'Guinea',
                'description' => 'Capitale de la Guinee',
            ],
            [
                'placeName' => 'Labe',
                'countryName' => 'Guinea',
                'description' => 'Ville de la Guinee',
            ],
            [
                'placeName' => 'Monrovia',
                'countryName' => 'Liberia',
                'description' => 'Capitale du Liberia',
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('places');
    }
};
