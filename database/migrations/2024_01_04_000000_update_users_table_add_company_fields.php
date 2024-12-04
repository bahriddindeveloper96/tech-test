<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTableAddCompanyFields extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add company fields (only for sellers)
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_tax_number')->nullable();
            $table->string('company_registration_number')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert company fields
            $table->dropColumn([
                'company_name',
                'company_address',
                'company_phone',
                'company_email',
                'company_tax_number',
                'company_registration_number'
            ]);
        });
    }
}
