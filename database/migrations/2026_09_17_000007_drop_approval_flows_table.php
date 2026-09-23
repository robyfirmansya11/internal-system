<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::dropIfExists('approval_flows'); }
    public function down(): void { /* Feature intentionally retired. */ }
};
