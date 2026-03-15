<?php
/**
 * Plugin Name: Compound Interest NISA Simulator
 * Plugin URI: https://github.com/yourname/compound-interest
 * Description: NISA向けの積立複利シミュレーターを表示するWordPressプラグインです。ApexChartsで資産推移を可視化します。
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/yourname
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: compound-interest
 */

if (!defined('ABSPATH')) {
    exit;
}

final class CI_NISA_Simulator {
    const VERSION = '1.0.0';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('nisa_simulator', array($this, 'render_shortcode'));
    }

    public function register_assets() {
        wp_register_style(
            'ci-nisa-simulator-style',
            plugin_dir_url(__FILE__) . 'assets/css/simulator.css',
            array(),
            self::VERSION
        );

        wp_register_script(
            'apexcharts',
            'https://cdn.jsdelivr.net/npm/apexcharts',
            array(),
            '3.49.2',
            true
        );

        wp_register_script(
            'ci-nisa-simulator-script',
            plugin_dir_url(__FILE__) . 'assets/js/simulator.js',
            array('apexcharts'),
            self::VERSION,
            true
        );

        wp_localize_script('ci-nisa-simulator-script', 'ciNisaConfig', array(
            'labels' => array(
                'finalAsset' => __('Final Asset', 'compound-interest'),
                'totalInvested' => __('Total Invested', 'compound-interest'),
                'totalProfit' => __('Profit', 'compound-interest'),
                'assetSeries' => __('Asset', 'compound-interest'),
                'investedSeries' => __('Invested Principal', 'compound-interest'),
            ),
            'currencySymbol' => html_entity_decode('&#165;'),
        ));
    }

    public function render_shortcode($atts = array()) {
        $defaults = array(
            'years' => 20,
            'annual_rate' => 5,
            'monthly_contribution' => 30000,
            'initial_amount' => 0,
        );

        $atts = shortcode_atts($defaults, $atts, 'nisa_simulator');

        $years = max(1, absint($atts['years']));
        $annual_rate = is_numeric($atts['annual_rate']) ? (float) $atts['annual_rate'] : 5;
        $monthly_contribution = is_numeric($atts['monthly_contribution']) ? (float) $atts['monthly_contribution'] : 30000;
        $initial_amount = is_numeric($atts['initial_amount']) ? (float) $atts['initial_amount'] : 0;

        wp_enqueue_style('ci-nisa-simulator-style');
        wp_enqueue_script('ci-nisa-simulator-script');

        $id = wp_unique_id('ci-nisa-');

        ob_start();
        ?>
        <div class="ci-nisa" id="<?php echo esc_attr($id); ?>"
            data-years="<?php echo esc_attr($years); ?>"
            data-annual-rate="<?php echo esc_attr($annual_rate); ?>"
            data-monthly-contribution="<?php echo esc_attr($monthly_contribution); ?>"
            data-initial-amount="<?php echo esc_attr($initial_amount); ?>">
            <div class="ci-nisa__panel">
                <h3 class="ci-nisa__title"><?php esc_html_e('NISA Compound Interest Simulator', 'compound-interest'); ?></h3>
                <div class="ci-nisa__grid">
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('Years', 'compound-interest'); ?></span>
                        <input type="number" min="1" max="60" step="1" name="years" value="<?php echo esc_attr($years); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('Annual Return (%)', 'compound-interest'); ?></span>
                        <input type="number" min="0" max="30" step="0.1" name="annualRate" value="<?php echo esc_attr($annual_rate); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('Monthly Contribution (JPY)', 'compound-interest'); ?></span>
                        <input type="number" min="0" step="1000" name="monthlyContribution" value="<?php echo esc_attr($monthly_contribution); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('Current Asset (JPY)', 'compound-interest'); ?></span>
                        <input type="number" min="0" step="10000" name="initialAmount" value="<?php echo esc_attr($initial_amount); ?>">
                    </label>
                </div>
                <button class="ci-nisa__button" type="button"><?php esc_html_e('Run Simulation', 'compound-interest'); ?></button>
            </div>

            <div class="ci-nisa__summary">
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('Final Asset', 'compound-interest'); ?></span>
                    <strong data-summary="finalAsset">-</strong>
                </div>
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('Total Invested', 'compound-interest'); ?></span>
                    <strong data-summary="totalInvested">-</strong>
                </div>
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('Profit', 'compound-interest'); ?></span>
                    <strong data-summary="totalProfit">-</strong>
                </div>
            </div>

            <div class="ci-nisa__chart" data-chart></div>
        </div>
        <?php

        return ob_get_clean();
    }
}

new CI_NISA_Simulator();
