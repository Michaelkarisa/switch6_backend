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

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->nullable();
            $table->integer('amount')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->nullable();
            $table->integer('amount')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('referees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('clubs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('city', 100)->nullable();
            $table->unsignedInteger('founded_year')->nullable();
            $table->string('stadium')->nullable();
            $table->string('manager')->nullable();
            $table->integer('jersey_color')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        Schema::create('leagues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('leaguename', 100);
            $table->string('name', 100)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('logo')->nullable();
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
            $table->timestamp('match_date')->nullable();
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->string('status')->default('scheduled');
            $table->string('venue')->nullable();
            $table->text('highlights')->nullable();
            $table->string('url')->nullable();
            $table->string('home_formation')->nullable();
            $table->string('away_formation')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
            $table->string('position', 50)->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('nationality')->nullable();
            $table->unsignedInteger('jersey_number')->nullable();
            $table->decimal('market_value', 14, 2)->nullable();
            $table->timestamps();
        });

         Schema::create('lineups', function (Blueprint $table) {
             $table->uuid('id')->primary();
             $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
             $table->foreignUuid('player_id')->nullable()->constrained('players')->cascadeOnDelete();
             $table->foreignUuid('club_id')->nullable()->constrained('clubs')->cascadeOnDelete();
             $table->string('position', 50)->nullable();
             $table->boolean('is_starter')->default(true);
             $table->integer('minute_in')->nullable();
             $table->integer('minute_out')->nullable();
             $table->timestamps();
             $table->unique(['match_id', 'player_id']); // ← add this
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

        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('match_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('minute')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();
        });

          Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary();

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


           Schema::create('ad_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('advertisement_id');
            $table->uuid('match_id')->nullable();

            $table->string('event_type', 30); // injected | played | completed
            $table->string('period')->nullable();

            $table->unsignedInteger('play_time')->nullable(); // seconds actually played
            $table->unsignedInteger('viewer_count')->nullable(); // optional live viewers snapshot

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

        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('match_id')->nullable()->index();
            $table->uuid('advertisement_id')->nullable()->index();
            $table->timestamps();
        });


        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 60);                    // e.g. Starter, Pro, Enterprise
            $table->string('slug', 60)->unique();          // starter | pro | enterprise
            $table->text('description')->nullable();
            $table->unsignedInteger('price_kes')->default(0);  // price in KES (smallest unit)
            $table->unsignedSmallInteger('duration_days')->default(30); // billing cycle
            $table->unsignedSmallInteger('max_matches')->nullable();    // null = unlimited
            $table->unsignedSmallInteger('max_streams')->nullable();
            $table->boolean('ads_enabled')->default(false);
            $table->boolean('analytics_enabled')->default(false);
            $table->boolean('most_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();          // extra feature flags as JSON
            $table->timestamps();
        });
 
        // ── User plan subscriptions ───────────────────────────────────────
        Schema::create('user_plan_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('status', 20)->default('active');
            // status: active | expired | cancelled | grace
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expiry_warning_sent_at')->nullable(); // track if 5-day email sent
            $table->uuid('payment_id')->nullable();        // FK set after ad_payments table exists
            $table->timestamps();
 
            $table->index(['user_id', 'status']);
            $table->index('expires_at');                   // used by the scheduler query
        });
 
        // ── Advertisement payments ────────────────────────────────────────
        Schema::create('ad_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('advertisement_id')->constrained('advertisements')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_kes');
            $table->string('currency', 10)->default('KES');
            $table->string('payment_method', 40)->default('mpesa');
            // mpesa | card | bank_transfer
            $table->string('mpesa_reference', 100)->nullable()->unique();
            $table->string('transaction_code', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            // pending | completed | failed | refunded
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();          // raw M-Pesa callback payload, etc.
            $table->timestamps();
 
            $table->index(['advertisement_id', 'status']);
            $table->index(['user_id', 'status']);
        });
 
        // ── Add FK from subscriptions → ad_payments (now that table exists) ─
        Schema::table('user_plan_subscriptions', function (Blueprint $table) {
            $table->foreign('payment_id')
                ->references('id')
                ->on('ad_payments')
                ->nullOnDelete();
        });
 
        // ── Upgrade payments table: link to user + plan ───────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('plan_id')
                ->nullable()->after('user_id')
                ->constrained('plans')->nullOnDelete();
            $table->string('type', 30)->default('subscription')->after('amount');
            // subscription | ad_payment | top_up
            $table->string('status', 20)->default('pending')->after('type');
            $table->string('reference', 100)->nullable()->after('status');
        });
 
        // ── Upgrade transactions table: link to user + plan ───────────────
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('plan_id')
                ->nullable()->after('user_id')
                ->constrained('plans')->nullOnDelete();
            $table->string('type', 30)->default('subscription')->after('status');
            $table->string('reference', 100)->nullable()->after('type');
            $table->string('provider', 40)->nullable()->after('reference');
            // mpesa | stripe | paypal
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('match_views');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('scorers');
        Schema::dropIfExists('lineups');
        Schema::dropIfExists('players');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('logins');
        Schema::dropIfExists('leagues');
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
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['user_id', 'plan_id', 'type', 'reference', 'provider']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['user_id', 'plan_id', 'type', 'status', 'reference']);
        });
        Schema::dropIfExists('ad_payments');
        Schema::dropIfExists('user_plan_subscriptions');
        Schema::dropIfExists('plans');
    }
};
