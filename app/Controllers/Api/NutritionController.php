<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class NutritionController extends ResourceController
{
    protected $format = 'json';

    public function constraints()
    {
        /**
         * =========================================================
         * 1️⃣ INPUT DASAR MANUSIA
         * =========================================================
         * age      → memengaruhi metabolisme & kebutuhan mikro
         * weight   → dasar perhitungan energi
         * height   → digunakan untuk BMI & BMR
         * gender   → faktor fisiologis BMR
         */
        $data = $this->request->getJSON(true);

        if (!isset($data['age'], $data['weight'], $data['height'], $data['gender'])) {
            return $this->failValidationErrors('Missing required fields');
        }

        $age    = $data['age'];
        $weight = $data['weight'];   // kg
        $height = $data['height'];   // cm
        $gender = strtolower($data['gender']);

        /**
         * =========================================================
         * 2️⃣ BODY MASS INDEX (BMI)
         * =========================================================
         * Rumus WHO:
         * BMI = berat (kg) / (tinggi (m))²
         *
         * Fungsi:
         * - Klasifikasi status tubuh
         * - Menentukan distribusi makronutrien
         */
        $heightM = $height / 100;
        $bmi = $weight / ($heightM * $heightM);

        $bmiCategory = match (true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25   => 'normal',
            $bmi < 30   => 'overweight',
            default     => 'obese'
        };

        /**
         * =========================================================
         * 3️⃣ BASAL METABOLIC RATE (BMR)
         * =========================================================
         * Metode: Mifflin–St Jeor Equation
         * Dianggap paling akurat untuk populasi modern
         *
         * Male   : (10W + 6.25H − 5A + 5)
         * Female : (10W + 6.25H − 5A − 161)
         *
         * Artinya:
         * → Energi minimal tubuh untuk bertahan hidup
         */
        $bmr = $gender === 'male'
            ? (10 * $weight) + (6.25 * $height) - (5 * $age) + 5
            : (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;

        /**
         * =========================================================
         * 4️⃣ TOTAL DAILY ENERGY EXPENDITURE (TDEE)
         * =========================================================
         * TDEE = BMR × Activity Factor
         *
         * Menggambarkan:
         * - Seberapa aktif gaya hidup seseorang
         * - Total kalori harian yang dibutuhkan
         */
        $activityFactor = match($data['activity_level'] ?? 'sedentary') {
            'light'    => 1.375,
            'moderate' => 1.55,
            'active'   => 1.725,
            default    => 1.2
        };

        $dailyCalories = intval($bmr * $activityFactor);

        /**
         * =========================================================
         * 5️⃣ KONDISI KESEHATAN
         * =========================================================
         * Digunakan untuk menyesuaikan batas gula, lemak, dan sodium
         */
        $conditions    = $data['conditions'] ?? [];
        $diabetes      = !empty($conditions['diabetes']);
        $heartDisease  = !empty($conditions['heart_disease']);
        $hypertension  = !empty($conditions['hypertension']);

        /**
         * =========================================================
         * 6️⃣ DISTRIBUSI MAKRONUTRIEN (AMDR)
         * =========================================================
         * Berdasarkan WHO / Institute of Medicine:
         * - Karbohidrat : 45–65%
         * - Protein     : 10–35%
         * - Lemak       : 20–35%
         *
         * Disesuaikan dengan BMI:
         * - Kurus → energi lebih banyak
         * - Obes → protein lebih tinggi
         */
        if ($bmiCategory === 'underweight') {
            $carbRatio    = 0.55;
            $proteinRatio = 0.20;
            $fatRatio     = 0.25;
        } elseif ($bmiCategory === 'obese') {
            $carbRatio    = 0.40;
            $proteinRatio = 0.30;
            $fatRatio     = 0.30;
        } else {
            $carbRatio    = 0.50;
            $proteinRatio = 0.25;
            $fatRatio     = 0.25;
        }

        /**
         * =========================================================
         * 7️⃣ KALORI PER PORSI
         * =========================================================
         * Digunakan sebagai standar mikroservice makanan
         * ±300 kcal umum dipakai untuk 1 serving
         */
        $servingCalories = 300;

        /**
         * =========================================================
         * 8️⃣ KONVERSI KALORI → GRAM (ATWATER SYSTEM)
         * =========================================================
         * Karbohidrat : 4 kkal / gram
         * Protein     : 4 kkal / gram
         * Lemak       : 9 kkal / gram
         */
        $carbMax    = intval(($servingCalories * $carbRatio) / 4);
        $proteinMin = intval(($servingCalories * $proteinRatio) / 4);
        $fatMax     = intval(($servingCalories * $fatRatio) / 9);

        /**
         * =========================================================
         * 9️⃣ MIKRONUTRIEN (RULE-BASED, PRAKTIS)
         * =========================================================
         * Angka mengikuti rekomendasi WHO & AHA (disederhanakan)
         */
        $sodiumMax = $hypertension ? 300 : ($age > 50 ? 400 : 500);
        $sugarMax  = $diabetes ? 5 : ($age < 18 ? 12 : 10);
        $fiberMin  = ($diabetes || $heartDisease || $age > 45) ? 6 : 4;

        /**
         * =========================================================
         * 🔟 RESPONSE API
         * =========================================================
         * Dibagi menjadi:
         * - meta        → informasi metabolik
         * - constraints → batas nutrisi makanan
         * - diet_flags  → label kondisi diet
         */
        return $this->respond([
            'meta' => [
                'age' => $age,
                'gender' => $gender,
                'bmi' => round($bmi, 1),
                'bmi_category' => $bmiCategory,
                'bmr' => intval($bmr),
                'daily_calorie_needs' => $dailyCalories
            ],
            'constraints' => [
                'max_calories_per_serving' => $servingCalories,
                'macros' => [
                    'carbohydrates' => ['max_g' => $carbMax],
                    'protein'       => ['min_g' => $proteinMin],
                    'fat'           => ['max_g' => $fatMax]
                ],
                'micros' => [
                    'sodium_mg_max'       => $sodiumMax,
                    'sugars_g_max'        => $sugarMax,
                    'dietary_fiber_g_min' => $fiberMin
                ]
            ],
            'diet_flags' => [
                'low_sugar'      => $diabetes,
                'low_sodium'     => $hypertension,
                'heart_friendly' => $heartDisease,
                'high_fiber'     => $fiberMin >= 6
            ]
        ]);
    }
}
