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
    const OPTION_KEY = 'ci_nisa_default_settings';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_shortcode('nisa_simulator', array($this, 'render_shortcode'));
    }

    private function get_base_defaults() {
        return array(
            'years' => 20,
            'annual_rate' => 5,
            'trust_fee' => 0.1,
            'monthly_contribution' => 30000,
            'initial_amount' => 0,
        );
    }

    private function sanitize_settings($input) {
        $defaults = $this->get_base_defaults();

        $sanitized = array();
        $sanitized['years'] = isset($input['years']) ? max(1, min(60, absint($input['years']))) : $defaults['years'];
        $sanitized['annual_rate'] = isset($input['annual_rate']) && is_numeric($input['annual_rate']) ? (float) $input['annual_rate'] : $defaults['annual_rate'];
        $sanitized['annual_rate'] = max(0, min(30, $sanitized['annual_rate']));
        $sanitized['trust_fee'] = isset($input['trust_fee']) && is_numeric($input['trust_fee']) ? (float) $input['trust_fee'] : $defaults['trust_fee'];
        $sanitized['trust_fee'] = max(0, min(5, $sanitized['trust_fee']));
        $sanitized['monthly_contribution'] = isset($input['monthly_contribution']) && is_numeric($input['monthly_contribution']) ? (float) $input['monthly_contribution'] : $defaults['monthly_contribution'];
        $sanitized['monthly_contribution'] = max(0, $sanitized['monthly_contribution']);
        $sanitized['initial_amount'] = isset($input['initial_amount']) && is_numeric($input['initial_amount']) ? (float) $input['initial_amount'] : $defaults['initial_amount'];
        $sanitized['initial_amount'] = max(0, $sanitized['initial_amount']);

        return $sanitized;
    }

    private function get_settings() {
        $saved = get_option(self::OPTION_KEY, array());
        $saved = is_array($saved) ? $saved : array();

        return array_merge($this->get_base_defaults(), $this->sanitize_settings($saved));
    }

    public function register_settings() {
        register_setting(
            'ci_nisa_settings_group',
            self::OPTION_KEY,
            array($this, 'sanitize_settings')
        );
    }

    public function register_admin_menu() {
        add_menu_page(
            __('複利計算機', 'compound-interest'),
            __('複利計算機', 'compound-interest'),
            'manage_options',
            'ci-compound-calculator',
            array($this, 'render_settings_page'),
            'dashicons-chart-line',
            60
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('複利計算機の設定', 'compound-interest'); ?></h1>
            <p><?php esc_html_e('フロントのショートコードに指定がない場合の初期値を設定できます。', 'compound-interest'); ?></p>
            <p>
                <strong><?php esc_html_e('利用ショートコード', 'compound-interest'); ?>:</strong>
                <input type="text" class="regular-text code" readonly value="[nisa_simulator]" onclick="this.select();">
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('ci_nisa_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="ci_nisa_years"><?php esc_html_e('初期値: 運用年数', 'compound-interest'); ?></label></th>
                            <td>
                                <input id="ci_nisa_years" name="<?php echo esc_attr(self::OPTION_KEY); ?>[years]" type="number" min="1" max="60" step="1" value="<?php echo esc_attr($settings['years']); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ci_nisa_annual_rate"><?php esc_html_e('初期値: 想定年利（%）', 'compound-interest'); ?></label></th>
                            <td>
                                <input id="ci_nisa_annual_rate" name="<?php echo esc_attr(self::OPTION_KEY); ?>[annual_rate]" type="number" min="0" max="30" step="0.1" value="<?php echo esc_attr($settings['annual_rate']); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ci_nisa_trust_fee"><?php esc_html_e('初期値: 信託報酬（年率%）', 'compound-interest'); ?></label></th>
                            <td>
                                <input id="ci_nisa_trust_fee" name="<?php echo esc_attr(self::OPTION_KEY); ?>[trust_fee]" type="number" min="0" max="5" step="0.01" value="<?php echo esc_attr($settings['trust_fee']); ?>" class="small-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ci_nisa_monthly_contribution"><?php esc_html_e('初期値: 毎月の積立額（円）', 'compound-interest'); ?></label></th>
                            <td>
                                <input id="ci_nisa_monthly_contribution" name="<?php echo esc_attr(self::OPTION_KEY); ?>[monthly_contribution]" type="number" min="0" step="1000" value="<?php echo esc_attr($settings['monthly_contribution']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ci_nisa_initial_amount"><?php esc_html_e('初期値: 現在の資産額（円）', 'compound-interest'); ?></label></th>
                            <td>
                                <input id="ci_nisa_initial_amount" name="<?php echo esc_attr(self::OPTION_KEY); ?>[initial_amount]" type="number" min="0" step="10000" value="<?php echo esc_attr($settings['initial_amount']); ?>" class="regular-text">
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('設定を保存', 'compound-interest')); ?>
            </form>
        </div>
        <?php
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
                'finalAsset' => __('最終資産', 'compound-interest'),
                'totalInvested' => __('投資元本合計', 'compound-interest'),
                'totalProfit' => __('運用益', 'compound-interest'),
                'assetSeries' => __('資産残高', 'compound-interest'),
                'investedSeries' => __('投資元本', 'compound-interest'),
            ),
            'currencySymbol' => html_entity_decode('&#165;'),
        ));
    }

    public function render_shortcode($atts = array()) {
        $defaults = $this->get_settings();

        $atts = shortcode_atts($defaults, $atts, 'nisa_simulator');

        $years = max(1, absint($atts['years']));
        $annual_rate = is_numeric($atts['annual_rate']) ? (float) $atts['annual_rate'] : 5;
        $trust_fee = is_numeric($atts['trust_fee']) ? (float) $atts['trust_fee'] : 0.1;
        $trust_fee = max(0, min(5, $trust_fee));
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
            data-trust-fee="<?php echo esc_attr($trust_fee); ?>"
            data-monthly-contribution="<?php echo esc_attr($monthly_contribution); ?>"
            data-initial-amount="<?php echo esc_attr($initial_amount); ?>">
            <div class="ci-nisa__panel">
                <h3 class="ci-nisa__title"><?php esc_html_e('NISA 積立シミュレーター', 'compound-interest'); ?></h3>
                <div class="ci-nisa__grid">
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('ファンド（選択で自動入力）', 'compound-interest'); ?></span>
                        <select name="fundPreset">
                            <option value=""><?php esc_html_e('カスタム（手動入力）', 'compound-interest'); ?></option>
                            <option value="emaxis_sp500" data-annual-rate="7.0" data-trust-fee="0.09372"><?php esc_html_e('ｅＭＡＸＩＳ Ｓｌｉｍ 米国株式（Ｓ＆Ｐ５００）', 'compound-interest'); ?></option>
                            <option value="emaxis_all_country" data-annual-rate="6.0" data-trust-fee="0.05775"><?php esc_html_e('ｅＭＡＸＩＳ Ｓｌｉｍ 全世界株式（オール・カントリー）', 'compound-interest'); ?></option>
                        </select>
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('運用年数', 'compound-interest'); ?></span>
                        <input type="number" min="1" max="60" step="1" name="years" value="<?php echo esc_attr($years); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('想定年利（%）', 'compound-interest'); ?></span>
                        <input type="number" min="0" max="30" step="0.1" name="annualRate" value="<?php echo esc_attr($annual_rate); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('信託報酬（年率%）', 'compound-interest'); ?></span>
                        <input type="number" min="0" max="5" step="0.01" name="trustFee" value="<?php echo esc_attr($trust_fee); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('毎月の積立額（円）', 'compound-interest'); ?></span>
                        <input type="number" min="0" step="1000" name="monthlyContribution" value="<?php echo esc_attr($monthly_contribution); ?>">
                    </label>
                    <label class="ci-nisa__field">
                        <span><?php esc_html_e('現在の資産額（円）', 'compound-interest'); ?></span>
                        <input type="number" min="0" step="10000" name="initialAmount" value="<?php echo esc_attr($initial_amount); ?>">
                    </label>
                </div>
                <button class="ci-nisa__button" type="button"><?php esc_html_e('シミュレーションする', 'compound-interest'); ?></button>

                <div class="ci-nisa__notes">
                    <p><?php esc_html_e('参考: 長期平均リターンの目安は、S&P500が年率約7%、オルカン（全世界株式）が年率約6%前後と言われます。', 'compound-interest'); ?></p>
                    <p><?php esc_html_e('ファンドを選択すると、想定年利と信託報酬の目安が自動入力されます。必要に応じて手動で調整してください。', 'compound-interest'); ?></p>
                    <p><?php esc_html_e('注意: 本ツールは将来の成果を保証するものではないシミュレーションです。為替変動、世界情勢や景気動向などの経済要因は考慮していません。実際の運用結果は市場環境・コスト・税制等で変動し、信託報酬も将来変更される可能性があります。', 'compound-interest'); ?></p>
                </div>
            </div>

            <div class="ci-nisa__summary">
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('実質年利（年利-信託報酬）', 'compound-interest'); ?></span>
                    <strong data-summary="netAnnualRate">-</strong>
                </div>
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('最終資産', 'compound-interest'); ?></span>
                    <strong data-summary="finalAsset">-</strong>
                </div>
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('投資元本合計', 'compound-interest'); ?></span>
                    <strong data-summary="totalInvested">-</strong>
                </div>
                <div class="ci-nisa__card">
                    <span><?php esc_html_e('運用益', 'compound-interest'); ?></span>
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
