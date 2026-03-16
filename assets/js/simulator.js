(function () {
  'use strict';

  function toNumber(value, fallback) {
    var num = Number(value);
    return Number.isFinite(num) ? num : fallback;
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function formatJPY(value, currencySymbol) {
    var rounded = Math.round(value);
    var formatted = rounded.toLocaleString('ja-JP');
    return (currencySymbol || '¥') + formatted;
  }

  function calculateSeries(years, annualRate, trustFee, monthlyContribution, initialAmount) {
    var effectiveAnnualRate = annualRate - trustFee;
    var monthRate = Math.pow(1 + effectiveAnnualRate / 100, 1 / 12) - 1;
    var months = years * 12;

    var balance = initialAmount;
    var invested = initialAmount;

    var labels = ['開始時点'];
    var assetData = [Math.round(balance)];
    var investedData = [Math.round(invested)];

    for (var m = 1; m <= months; m += 1) {
      balance = balance * (1 + monthRate) + monthlyContribution;
      invested += monthlyContribution;

      if (m % 12 === 0) {
        labels.push((m / 12) + '年目');
        assetData.push(Math.round(balance));
        investedData.push(Math.round(invested));
      }
    }

    return {
      labels: labels,
      assetData: assetData,
      investedData: investedData,
      effectiveAnnualRate: effectiveAnnualRate,
      finalAsset: balance,
      totalInvested: invested,
      totalProfit: balance - invested,
    };
  }

  function initSimulator(container) {
    var chartElement = container.querySelector('[data-chart]');
    var button = container.querySelector('.ci-nisa__button');
    var labels = (window.ciNisaConfig && window.ciNisaConfig.labels) || {};
    var currencySymbol = (window.ciNisaConfig && window.ciNisaConfig.currencySymbol) || '¥';

    var inputs = {
      fundPreset: container.querySelector('select[name="fundPreset"]'),
      years: container.querySelector('input[name="years"]'),
      annualRate: container.querySelector('input[name="annualRate"]'),
      trustFee: container.querySelector('input[name="trustFee"]'),
      monthlyContribution: container.querySelector('input[name="monthlyContribution"]'),
      initialAmount: container.querySelector('input[name="initialAmount"]'),
    };

    var summary = {
      netAnnualRate: container.querySelector('[data-summary="netAnnualRate"]'),
      finalAsset: container.querySelector('[data-summary="finalAsset"]'),
      totalInvested: container.querySelector('[data-summary="totalInvested"]'),
      totalProfit: container.querySelector('[data-summary="totalProfit"]'),
    };

    var chart = null;

    function applyPreset() {
      if (!inputs.fundPreset) {
        return;
      }

      var selectedOption = inputs.fundPreset.options[inputs.fundPreset.selectedIndex];
      var presetAnnualRate = toNumber(selectedOption.getAttribute('data-annual-rate'), NaN);
      var presetTrustFee = toNumber(selectedOption.getAttribute('data-trust-fee'), NaN);

      if (Number.isFinite(presetAnnualRate) && Number.isFinite(presetTrustFee)) {
        inputs.annualRate.value = presetAnnualRate.toFixed(1);
        inputs.trustFee.value = presetTrustFee.toFixed(5);
      }

      runSimulation();
    }

    function runSimulation() {
      var years = clamp(toNumber(inputs.years.value, 20), 1, 60);
      var annualRate = clamp(toNumber(inputs.annualRate.value, 5), 0, 30);
      var trustFee = clamp(toNumber(inputs.trustFee.value, 0.1), 0, 5);
      var monthlyContribution = Math.max(0, toNumber(inputs.monthlyContribution.value, 30000));
      var initialAmount = Math.max(0, toNumber(inputs.initialAmount.value, 0));

      var result = calculateSeries(years, annualRate, trustFee, monthlyContribution, initialAmount);

      summary.netAnnualRate.textContent = result.effectiveAnnualRate.toFixed(2) + '%';
      summary.finalAsset.textContent = formatJPY(result.finalAsset, currencySymbol);
      summary.totalInvested.textContent = formatJPY(result.totalInvested, currencySymbol);
      summary.totalProfit.textContent = formatJPY(result.totalProfit, currencySymbol);

      var options = {
        chart: {
          type: 'area',
          height: 360,
          toolbar: { show: false },
          fontFamily: 'Hiragino Kaku Gothic ProN, Meiryo, sans-serif'
        },
        colors: ['#0f766e', '#f59e0b'],
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 0.2,
            opacityFrom: 0.45,
            opacityTo: 0.08,
            stops: [0, 90, 100]
          }
        },
        tooltip: {
          y: {
            formatter: function (value) {
              return formatJPY(value, currencySymbol);
            }
          }
        },
        series: [
          {
            name: labels.assetSeries || '資産残高',
            data: result.assetData
          },
          {
            name: labels.investedSeries || '投資元本',
            data: result.investedData
          }
        ],
        xaxis: {
          categories: result.labels
        },
        yaxis: {
          labels: {
            formatter: function (value) {
              return formatJPY(value, currencySymbol);
            }
          }
        },
        legend: {
          position: 'top'
        }
      };

      if (chart) {
        chart.updateOptions(options, true, true);
      } else {
        chart = new ApexCharts(chartElement, options);
        chart.render();
      }
    }

    button.addEventListener('click', runSimulation);

    if (inputs.fundPreset) {
      inputs.fundPreset.addEventListener('change', applyPreset);
    }

    runSimulation();
  }

  function bootstrap() {
    var containers = document.querySelectorAll('.ci-nisa');
    containers.forEach(initSimulator);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
