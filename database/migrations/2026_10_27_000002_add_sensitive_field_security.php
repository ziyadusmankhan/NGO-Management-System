<?php

use App\Domain\Security\Services\SensitiveFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_state', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value');
        });
        DB::table('security_state')->insert(['key' => 'encryption', 'value' => 'converting']);
        foreach (SensitiveFields::TABLES as $table => $fields) {
            // Later modules can opt into the same encrypted casts. Their empty tables
            // are created after this migration on a fresh installation.
            if (! Schema::hasTable($table)) {
                continue;
            }
            // Keep source column names and data. Widen non-TEXT columns for authenticated ciphertext.
            $columns = collect(Schema::getColumns($table))->keyBy('name');
            foreach ($fields as $field) {
                $info = $columns[$field];
                if (! in_array(strtolower($info['type_name']), ['text', 'longtext', 'mediumtext'])) {
                    // Member CNIC uniqueness moves to its keyed lookup, including archived members.
                    if ($table === 'members' && $field === 'cnic') {
                        Schema::table($table, fn (Blueprint $t) => $t->dropUnique(['cnic']));
                    }
                    Schema::table($table, fn (Blueprint $t) => $t->text($field)->nullable($info['nullable'])->change());
                }
                if (SensitiveFields::indexed($field)) {
                    Schema::table($table, function (Blueprint $t) use ($field, $table) {
                        $c = $t->string($field.'_lookup', 64)->nullable();
                        if ($table === 'members' && $field === 'cnic') {
                            $c->unique();
                        } else {
                            $c->index();
                        }
                    });
                }
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the verified pre-upgrade backup and matching keys instead of dropping encryption state.');
    }
};
