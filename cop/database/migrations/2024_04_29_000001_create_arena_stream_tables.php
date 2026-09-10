<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('module')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index('idx_idke_user_id');
            $table->longText('response');
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->timestamps();
            $table->foreign('user_id', 'fk_idke_user_id_u')
                ->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('referees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('profile_url')->nullable();
            $table->string('slug')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('clubs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('city', 100)->nullable();
            $table->unsignedInteger('founded_year')->nullable();
            $table->string('stadium')->nullable();
            $table->string('slug')->nullable();
            $table->integer('jersey_color')->nullable();
            $table->string('logo_url')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

            Schema::create('club_managers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('role')->nullable();
            $table->string('profile_url')->nullable();
            $table->string('slug')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leagues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->string('short_name', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('logins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->foreignUuid('home_club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('away_club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('referee_id')->nullable()->constrained('referees')->nullOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->timestamp('match_date')->nullable();
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->string('status')->default('scheduled');
            $table->string('venue')->nullable();
            $table->text('highlights')->nullable();
            $table->json('streamkey')->nullable();
            $table->string('home_formation')->nullable();
            $table->string('away_formation')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('position', 50)->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('nationality')->nullable();
            $table->unsignedInteger('jersey_number')->nullable();
            $table->decimal('market_value', 14, 2)->nullable();
            $table->string('slug')->nullable();
            $table->string('role')->nullable();
            $table->string('profile_url')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
        });
//usefull to track player transfers.
        Schema::create('player_history', function (Blueprint $table){
            $table->uuid('id')->primary();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });

         Schema::create('lineups', function (Blueprint $table) {
             $table->uuid('id')->primary();
             $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
             $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
             $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
             $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
             $table->string('position', 50)->nullable();
             $table->boolean('is_starter')->default(true);
             $table->integer('minute_in')->nullable();
             $table->integer('minute_out')->nullable();
             $table->timestamps();
             $table->unique(['match_id', 'player_id']); // ← add this
          });

        schema::create('player_cards', function (Blueprint $table){
             $table->uuid('id')->primary();
             $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
             $table->foreignUuid('league_id')->nullable()->constrained('leagues')->cascadeOnDelete();
             $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
             $table->unsignedSmallInteger('minute')->nullable();
             $table->string('card')->default("None");
             $table->timestamps();
        });

        Schema::create('scorers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->integer('minute')->nullable();
            $table->string('goal_type')->default('regular');
            $table->foreignUuid('assist_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();
        });

        
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 60);                    // e.g. Starter, Pro, Enterprise
            $table->string('slug', 60)->unique();          // starter | pro | enterprise
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);  // price in KES (smallest unit)
            $table->string('currency', 10)->default('KES');
            $table->unsignedSmallInteger('duration_days')->default(30); // billing cycle
            $table->unsignedSmallInteger('max_matches')->nullable();    // null = unlimited
            $table->unsignedSmallInteger('max_cameras')->nullable();
            $table->json('quality')->nullable();
            $table->boolean('ads_enabled')->default(false);
            $table->boolean('analytics_enabled')->default(false);
            $table->boolean('most_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();          // extra feature flags as JSON
            $table->timestamps();
        });
 
                  // ── payments ────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 10)->default('KES');
            $table->string('payment_method', 40)->default('mpesa');
            $table->string('phone', 20)->nullable();
             // subscription | advertisement   
            $table->string('type', 30)->default('subscription');
            // mpesa | card | bank_transfer
            $table->string('reference', 100)->nullable()->unique();
            $table->string('transaction_code', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            // pending | completed | failed | refunded
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();  
            $table->timestamps();
           // $table->index(['advertisement_id', 'status']);
            $table->index(['user_id', 'status']);
          //  $table->index(['subscription_id', 'status']);
        });
      // ── User plan subscriptions ───────────────────────────────────────
        Schema::create('user_plan_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('quality')->default(480);
            // status: active | expired | cancelled | grace
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expiry_warning_sent_at')->nullable(); // track if 5-day email sent       // FK set after ad_payments table exists
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');                   // used by the scheduler query
        });
         
          Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('slug')->nullable();
            $table->string('title');
            $table->string('file_type', 20); // image | video
            $table->string('file_path');
            $table->unsignedInteger('duration')->default(15);
            $table->string('period')->nullable(); // before_1st | halftime | after_2nd | etc
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active | paused | expired
            $table->json('target_tags')->nullable();
            $table->timestamps();
        });
        //on clubs advertise have an option for clubs to advertise to self(only allow images, charge only 50bob,will cover whole match period, should be a small size)
        //change so that adveritisers can choose to advertise on specific matches. they can bid for the match slots for those matches highest bidder wins.


           Schema::create('ad_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('advertisement_id');
            $table->uuid('match_id')->nullable();
            $table->string('event_type', 30); // injected | played | completed
            $table->string('period')->nullable();
            $table->unsignedInteger('play_time')->nullable(); // seconds actually played
            $table->unsignedInteger('view_count')->nullable(); // optional live views snapshot
            $table->string('platform')->nullable(); // youtube | facebook | rtmp_custom
            $table->timestamps();
            $table->foreign('advertisement_id')
                ->references('id')
                ->on('advertisements')
                ->cascadeOnDelete();
            $table->foreign('match_id')
                ->references('id')
                ->on('matches')
                ->nullOnDelete();
        });

        // ── Upgrade transactions table: link to payment ───────────────
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status', 50)->nullable();
            $table->string('transaction_code')->nullable();
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->json('metadata')->nullable(); 
            // mpesa | stripe | paypal
            $table->string('provider', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('share_lineup', function (Blueprint $table){
            $table->uuid('id')->primary();
            $table->foreignUuid('sender_id')
                ->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('recepient_id')
                ->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->nullOnDelete(); 
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->nullOnDelete(); 
            $table->timestamps();
        });

        Schema::create('match_bids',function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->nullOnDelete();
             $table->foreignUuid('advertisement_id')->nullable()->constrained('advertisements')->nullOnDelete();
             $table->string('slot')->nullable();
             $table->unsignedBigInteger('amaount')->default(0);
             $table->string('currency')->default('KES');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('scorers');
        Schema::dropIfExists('lineups');
        Schema::dropIfExists('players');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('logins');
        Schema::dropIfExists('leagues');
        Schema::dropIfExists('players_cards');
        Schema::dropIfExists('clubs');
        Schema::dropIfExists('referees');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('logs');
        Schema::table('user_plan_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'reference', 'provider']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'type', 'status', 'reference']);
        });
        Schema::dropIfExists('user_plan_subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('share_lineup');
        Schema::dropIfExists('club_managers');
        Schema::dropIfExists('match_bids');
        Schema::dropIfExists('player_history');
    }
};
