=== Compound Interest NISA Simulator ===
Contributors: yourname
Tags: nisa, compound interest, calculator, investment, simulator
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPressでNISA向け積立シミュレーションを表示するプラグインです。ApexChartsで資産推移を可視化します。

== Description ==
このプラグインは、NISAでよく使う以下の入力項目をもとに将来資産を計算します。

- 年数
- 年利（%）
- 毎月の積立額
- 現在の資産額

ショートコードを配置するだけで、複利計算結果をグラフで表示できます。

== Installation ==
1. GitHubのこのリポジトリをZIPでダウンロードします。
2. WordPress管理画面で「プラグイン > 新規追加 > プラグインのアップロード」を開きます。
3. ダウンロードしたZIPを選択してインストールします。
4. プラグインを有効化します。
5. 投稿や固定ページに `[nisa_simulator]` を追加します。

== Frequently Asked Questions ==
= 入力値の初期値を変更できますか？ =
はい。ショートコード属性で変更できます。

例: `[nisa_simulator years="15" annual_rate="4.2" monthly_contribution="50000" initial_amount="1000000"]`

== Changelog ==
= 1.0.0 =
- 初回リリース
- NISA向け積立複利シミュレーターを追加
- ApexChartsによる資産推移グラフを追加
