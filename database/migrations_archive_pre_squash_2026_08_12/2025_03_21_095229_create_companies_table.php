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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('logo')->nullable();
            $table->text('about');
            $table->timestamps();
        });
        DB::table('companies')->insert([
            'name' => 'EDAAG TRADING',
            'address' => 'Kobaya, C/Ratoma, Conakry',
            'logo' => 'logo.png',
            'about' => 'Le manager principale du site, il est chargé de la gestion complète et mis à jour du site',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
