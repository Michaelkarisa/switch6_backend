<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = DB::table('clubs')->pluck('id', 'name');

        $gorId      = $clubs['Gor Mahia FC'];
        $afcId      = $clubs['AFC Leopards'];
        $tuskerId   = $clubs['Tusker FC'];
        $kakamegaId = $clubs['Kakamega Homeboyz'];
        $mathareId  = $clubs['Mathare United'];
        $ulinziId   = $clubs['Ulinzi Stars'];

        $players = [

            /*
            |--------------------------------------------------------------------------
            | Gor Mahia FC
            |--------------------------------------------------------------------------
            */
            ['name' => 'Shafik Batambuze',  'club_id' => $gorId, 'position' => 'GK',  'age' => 28, 'nationality' => 'Ugandan',   'jersey_number' => 1,  'market_value' => 15000],
            ['name' => 'David Mapigano',    'club_id' => $gorId, 'position' => 'GK',  'age' => 24, 'nationality' => 'Tanzanian', 'jersey_number' => 16, 'market_value' => 10000],

            ['name' => 'Joash Onyango',     'club_id' => $gorId, 'position' => 'CB',  'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 20000],
            ['name' => 'Philemon Otieno',   'club_id' => $gorId, 'position' => 'LCB', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 17000],
            ['name' => 'David Ochieng',     'club_id' => $gorId, 'position' => 'RCB', 'age' => 29, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 12000],
            ['name' => 'Wellington Ochieng','club_id' => $gorId, 'position' => 'LB',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 14000],
            ['name' => 'Joachim Oluoch',    'club_id' => $gorId, 'position' => 'RB',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 13000],

            ['name' => 'Benson Omalla',     'club_id' => $gorId, 'position' => 'CDM', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 18000],
            ['name' => 'Sylvester Owino',   'club_id' => $gorId, 'position' => 'CM',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 15, 'market_value' => 15000],
            ['name' => 'Kenneth Muguna',    'club_id' => $gorId, 'position' => 'LCM', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 22000],
            ['name' => 'Clifton Miheso',    'club_id' => $gorId, 'position' => 'RCM', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 14, 'market_value' => 18000],
            ['name' => 'Camille Woungounga','club_id' => $gorId, 'position' => 'CAM', 'age' => 27, 'nationality' => 'Congolese', 'jersey_number' => 17, 'market_value' => 24000],

            ['name' => 'Dickson Ambundo',   'club_id' => $gorId, 'position' => 'LW',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 11, 'market_value' => 18000],
            ['name' => 'Austine Odhiambo',  'club_id' => $gorId, 'position' => 'RW',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 17000],

            ['name' => 'Nicholas Kipkirui', 'club_id' => $gorId, 'position' => 'ST',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 9,  'market_value' => 30000],
            ['name' => 'Jules Ulimwengu',   'club_id' => $gorId, 'position' => 'CF',  'age' => 25, 'nationality' => 'Tanzanian', 'jersey_number' => 10, 'market_value' => 27000],

            /*
            |--------------------------------------------------------------------------
            | AFC Leopards
            |--------------------------------------------------------------------------
            */
            ['name' => 'Benjamin Ochan',    'club_id' => $afcId, 'position' => 'GK',  'age' => 30, 'nationality' => 'Ugandan', 'jersey_number' => 1,  'market_value' => 12000],
            ['name' => 'Levis Opiyo',       'club_id' => $afcId, 'position' => 'GK',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 7000],

            ['name' => 'Robinson Kamura',   'club_id' => $afcId, 'position' => 'CB',  'age' => 28, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 15000],
            ['name' => 'Anthony Akumu',     'club_id' => $afcId, 'position' => 'LCB', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 14000],
            ['name' => 'Duncan Otieno',     'club_id' => $afcId, 'position' => 'RCB', 'age' => 30, 'nationality' => 'Kenyan', 'jersey_number' => 15, 'market_value' => 10000],
            ['name' => 'Isaac Kipyegon',    'club_id' => $afcId, 'position' => 'LB',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 11000],
            ['name' => 'Marvin Nabwire',    'club_id' => $afcId, 'position' => 'RB',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 10000],

            ['name' => 'John Mark Makwatta','club_id' => $afcId, 'position' => 'CDM', 'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 15000],
            ['name' => 'Clyde Senaji',      'club_id' => $afcId, 'position' => 'CM',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 10, 'market_value' => 17000],
            ['name' => 'Isaac Kipkemei',    'club_id' => $afcId, 'position' => 'LCM', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 14000],
            ['name' => 'Kevin Kimani',      'club_id' => $afcId, 'position' => 'CAM', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 17, 'market_value' => 14000],

            ['name' => 'Ali Abondo',        'club_id' => $afcId, 'position' => 'LW',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 11, 'market_value' => 13000],
            ['name' => 'Jared Oluoch',      'club_id' => $afcId, 'position' => 'RW',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 11000],

            ['name' => 'Elvis Rupia',       'club_id' => $afcId, 'position' => 'ST',  'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 9,  'market_value' => 28000],
            ['name' => 'Whyvonne Isuza',    'club_id' => $afcId, 'position' => 'CF',  'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 19, 'market_value' => 16000],

            /*
            |--------------------------------------------------------------------------
            | Tusker FC
            |--------------------------------------------------------------------------
            */
            ['name' => 'Patrick Matasi',    'club_id' => $tuskerId, 'position' => 'GK',  'age' => 29, 'nationality' => 'Kenyan', 'jersey_number' => 1,  'market_value' => 14000],
            ['name' => 'Daniel Otieno',     'club_id' => $tuskerId, 'position' => 'GK',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 16, 'market_value' => 7000],

            ['name' => 'Humphrey Mieno',    'club_id' => $tuskerId, 'position' => 'CB',  'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 16000],
            ['name' => 'David Owino',       'club_id' => $tuskerId, 'position' => 'LCB', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 14000],
            ['name' => 'Lawrence Juma',     'club_id' => $tuskerId, 'position' => 'RCB', 'age' => 30, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 10000],
            ['name' => 'Joseph Okumu',      'club_id' => $tuskerId, 'position' => 'LB',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 25000],
            ['name' => 'Rodgers Ouma',      'club_id' => $tuskerId, 'position' => 'RB',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 13000],

            ['name' => 'Kevin Okoth',       'club_id' => $tuskerId, 'position' => 'CDM', 'age' => 28, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 16000],
            ['name' => 'Boniface Muchiri',  'club_id' => $tuskerId, 'position' => 'CM',  'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 18000],
            ['name' => 'John Kariuki',      'club_id' => $tuskerId, 'position' => 'RCM', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 14, 'market_value' => 15000],
            ['name' => 'Eugene Asike',      'club_id' => $tuskerId, 'position' => 'CAM', 'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 10, 'market_value' => 17000],

            ['name' => 'Dedan Otieno',      'club_id' => $tuskerId, 'position' => 'LW',  'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 11, 'market_value' => 12000],
            ['name' => 'Enosh Ochieng',     'club_id' => $tuskerId, 'position' => 'RW',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 13000],

            ['name' => 'Eric Zakayo',       'club_id' => $tuskerId, 'position' => 'ST',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 9,  'market_value' => 25000],
            ['name' => 'Chrispinus Otieno', 'club_id' => $tuskerId, 'position' => 'CF',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 19, 'market_value' => 19000],

            /*
            |--------------------------------------------------------------------------
            | Kakamega Homeboyz
            |--------------------------------------------------------------------------
            */
            ['name' => 'Ian Otieno',        'club_id' => $kakamegaId, 'position' => 'GK',  'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 1,  'market_value' => 11000],
            ['name' => 'Vincent Oburu',     'club_id' => $kakamegaId, 'position' => 'GK',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 16, 'market_value' => 6000],

            ['name' => 'Shami Kibwana',     'club_id' => $kakamegaId, 'position' => 'CB',  'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 13000],
            ['name' => 'Moses Mudavadi',    'club_id' => $kakamegaId, 'position' => 'LCB', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 12000],
            ['name' => 'Dennis Nganga',     'club_id' => $kakamegaId, 'position' => 'RB',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 12000],
            ['name' => 'Bernard Mangoli',   'club_id' => $kakamegaId, 'position' => 'LB',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 11000],

            ['name' => 'Derrick Otanga',    'club_id' => $kakamegaId, 'position' => 'CDM', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 13000],
            ['name' => 'Henry Meja',        'club_id' => $kakamegaId, 'position' => 'CM',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 15000],
            ['name' => 'Kelvin Egessa',     'club_id' => $kakamegaId, 'position' => 'RCM', 'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 14, 'market_value' => 11000],
            ['name' => 'Ezekiel Odera',     'club_id' => $kakamegaId, 'position' => 'CAM', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 10, 'market_value' => 19000],

            ['name' => 'Austine Odhiambo',  'club_id' => $kakamegaId, 'position' => 'LW',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 11, 'market_value' => 14000],
            ['name' => 'Ronney Otieno',     'club_id' => $kakamegaId, 'position' => 'RW',  'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 10000],

            ['name' => 'William Wadri',     'club_id' => $kakamegaId, 'position' => 'ST',  'age' => 26, 'nationality' => 'Ugandan', 'jersey_number' => 9,  'market_value' => 22000],
            ['name' => 'Johnstone Omurwa',  'club_id' => $kakamegaId, 'position' => 'CF',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 17, 'market_value' => 16000],

            /*
            |--------------------------------------------------------------------------
            | Mathare United
            |--------------------------------------------------------------------------
            */
            ['name' => 'James Saruni',      'club_id' => $mathareId, 'position' => 'GK', 'age' => 28, 'nationality' => 'Kenyan', 'jersey_number' => 1,  'market_value' => 10000],
            ['name' => 'George Abege',      'club_id' => $mathareId, 'position' => 'GK', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 16, 'market_value' => 6000],

            ['name' => 'Daniel Mwaura',     'club_id' => $mathareId, 'position' => 'CB',  'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 12000],
            ['name' => 'Sammy Meja',        'club_id' => $mathareId, 'position' => 'LCB', 'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 11000],
            ['name' => 'Cliff Nyakeya',     'club_id' => $mathareId, 'position' => 'RB',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 11000],
            ['name' => 'Kevin Omondi',      'club_id' => $mathareId, 'position' => 'LB',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 10000],

            ['name' => 'Peter Thiongo',     'club_id' => $mathareId, 'position' => 'CDM', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 12000],
            ['name' => 'Paul Kiongera',     'club_id' => $mathareId, 'position' => 'CM',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 14000],
            ['name' => 'Erick Kapaito',     'club_id' => $mathareId, 'position' => 'RCM', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 14, 'market_value' => 13000],
            ['name' => 'George Odhiambo',   'club_id' => $mathareId, 'position' => 'CAM', 'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 10, 'market_value' => 16000],

            ['name' => 'Ibrahim Joshua',    'club_id' => $mathareId, 'position' => 'LW',  'age' => 21, 'nationality' => 'Ugandan', 'jersey_number' => 11, 'market_value' => 13000],
            ['name' => 'Brian Kamau',       'club_id' => $mathareId, 'position' => 'RW',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 11000],

            ['name' => 'Tyson Otieno',      'club_id' => $mathareId, 'position' => 'ST',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 9,  'market_value' => 20000],
            ['name' => 'Elvis Nandwa',      'club_id' => $mathareId, 'position' => 'CF',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 19, 'market_value' => 14000],

            /*
            |--------------------------------------------------------------------------
            | Ulinzi Stars
            |--------------------------------------------------------------------------
            */
            ['name' => 'Ezekiel Owade',     'club_id' => $ulinziId, 'position' => 'GK', 'age' => 29, 'nationality' => 'Kenyan', 'jersey_number' => 1,  'market_value' => 10000],
            ['name' => 'Felix Oluoch',      'club_id' => $ulinziId, 'position' => 'GK', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 16, 'market_value' => 6000],

            ['name' => 'Boniface Otieno',   'club_id' => $ulinziId, 'position' => 'CB',  'age' => 28, 'nationality' => 'Kenyan', 'jersey_number' => 5,  'market_value' => 12000],
            ['name' => 'Michael Kibwage',   'club_id' => $ulinziId, 'position' => 'LCB', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 6,  'market_value' => 11000],
            ['name' => 'Nahashon Ouma',     'club_id' => $ulinziId, 'position' => 'RB',  'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 2,  'market_value' => 11000],
            ['name' => 'Oliver Maloba',     'club_id' => $ulinziId, 'position' => 'LB',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 3,  'market_value' => 10000],

            ['name' => 'Christopher Oruchum','club_id' => $ulinziId, 'position' => 'CDM', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 4,  'market_value' => 12000],
            ['name' => 'Victor Majid',      'club_id' => $ulinziId, 'position' => 'CM',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 8,  'market_value' => 14000],
            ['name' => 'Kennedy Owino',     'club_id' => $ulinziId, 'position' => 'RCM', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 14, 'market_value' => 13000],
            ['name' => 'Amos Nondi',        'club_id' => $ulinziId, 'position' => 'CAM', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 10, 'market_value' => 16000],

            ['name' => 'Moses Mudde',       'club_id' => $ulinziId, 'position' => 'LW',  'age' => 22, 'nationality' => 'Ugandan', 'jersey_number' => 11, 'market_value' => 13000],
            ['name' => 'Philip Mayieka',    'club_id' => $ulinziId, 'position' => 'RW',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 7,  'market_value' => 12000],

            ['name' => 'George Odhiambo',   'club_id' => $ulinziId, 'position' => 'ST',  'age' => 26, 'nationality' => 'Kenyan', 'jersey_number' => 9,  'market_value' => 21000],
            ['name' => 'Fred Nkata',        'club_id' => $ulinziId, 'position' => 'CF',  'age' => 23, 'nationality' => 'Ugandan', 'jersey_number' => 19, 'market_value' => 17000],

            // Additional players so every club has at least 18 players

// ── Gor Mahia FC (16 -> 18) ─────────────────────────────
['name' => 'Fredrick Odhiambo',  'club_id' => $gorId,      'position' => 'LWB', 'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 9000],
['name' => 'Timothy Ouma',       'club_id' => $gorId,      'position' => 'RM',  'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 21, 'market_value' => 8500],

// ── AFC Leopards (16 -> 18) ─────────────────────────────
['name' => 'Victor Oburu',       'club_id' => $afcId,      'position' => 'LM',  'age' => 20, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 7500],
['name' => 'Collins Waweru',     'club_id' => $afcId,      'position' => 'RDM', 'age' => 27, 'nationality' => 'Kenyan', 'jersey_number' => 21, 'market_value' => 9500],

// ── Tusker FC (16 -> 18) ────────────────────────────────
['name' => 'Brian Marita',       'club_id' => $tuskerId,   'position' => 'LM',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 17, 'market_value' => 11000],
['name' => 'Dennis Wanjala',     'club_id' => $tuskerId,   'position' => 'RWB', 'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 9000],

// ── Kakamega Homeboyz (14 -> 18) ────────────────────────
['name' => 'Gilbert Wandera',    'club_id' => $kakamegaId, 'position' => 'CM',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 8000],
['name' => 'Brian Wekesa',       'club_id' => $kakamegaId, 'position' => 'RCB', 'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 19, 'market_value' => 8500],
['name' => 'Dennis Odongo',      'club_id' => $kakamegaId, 'position' => 'LWB', 'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 7000],
['name' => 'Allan Masika',       'club_id' => $kakamegaId, 'position' => 'SS',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 21, 'market_value' => 9500],

// ── Mathare United (14 -> 18) ───────────────────────────
['name' => 'Collins Ochieng',    'club_id' => $mathareId,  'position' => 'CM',  'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 17, 'market_value' => 9000],
['name' => 'Dennis Ouma',        'club_id' => $mathareId,  'position' => 'RCB', 'age' => 29, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 8000],
['name' => 'Kevin Maina',        'club_id' => $mathareId,  'position' => 'LAM', 'age' => 21, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 8500],
['name' => 'Sammy Ouko',         'club_id' => $mathareId,  'position' => 'RS',  'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 21, 'market_value' => 9500],

// ── Ulinzi Stars (14 -> 18) ─────────────────────────────
['name' => 'Samuel Waweru',      'club_id' => $ulinziId,   'position' => 'CM',  'age' => 25, 'nationality' => 'Kenyan', 'jersey_number' => 17, 'market_value' => 10000],
['name' => 'Brian Otieno',       'club_id' => $ulinziId,   'position' => 'RCB', 'age' => 24, 'nationality' => 'Kenyan', 'jersey_number' => 18, 'market_value' => 8500],
['name' => 'Joseph Onyango',     'club_id' => $ulinziId,   'position' => 'LWB', 'age' => 22, 'nationality' => 'Kenyan', 'jersey_number' => 20, 'market_value' => 8000],
['name' => 'Kelvin Mumo',        'club_id' => $ulinziId,   'position' => 'SS',  'age' => 23, 'nationality' => 'Kenyan', 'jersey_number' => 21, 'market_value' => 9500],
        ];

        $records = array_map(function ($player) {
            return array_merge($player, [
                'id' => (string) Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $players);

        DB::table('players')->insert($records);
    }
}