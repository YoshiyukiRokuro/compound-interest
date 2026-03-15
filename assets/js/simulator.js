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

  function calculateSeries(years, annualRate, monthlyContribution, initialAmount) {
    var monthRate = Math.pow(1 + annualRate / 100, 1 / 12) - 1;
    var months = years * 12;

    var balance = initialAmount;
    var invested = initialAmount;

    var labels = ['Start'];
    var assetData = [Math.round(balance)];
    var investedData = [Math.round(invested)];

    for (var m = 1; m <= months; m += 1) {
      balance = balance * (1 + monthRate) + monthlyContribution;
      invested += monthlyContribution;

      if (m % 12 === 0) {
        labels.push('Year ' + m / 12);
        assetData.push(Math.round(balance));
        investedData.push(Math.round(invested));
      }
    }

    return {
      labels: labels,
      assetData: assetData,
      investedData: investedData,
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
      years: container.querySelector('input[name="years"]'),
      annualRate: container.querySelector('input[name="annualRate"]'),
      monthlyContribution: container.querySelector('input[name="monthlyContribution"]'),
      initialAmount: container.querySelector('input[name="initialAmount"]'),
    };

    var summary = {
      finalAsset: container.querySelector('[data-summary="finalAsset"]'),
      totalInvested: container.querySelector('[data-summary="totalInvested"]'),
      totalProfit: container.querySelector('[data-summary="totalProfit"]'),
    };

    var chart = null;

    function runSimulation() {
      var years = clamp(toNumber(inputs.years.value, 20), 1, 60);
      var annualRate = clamp(toNumber(inputs.annualRate.value, 5), 0, 30);
      var monthlyContribution = Math.max(0, toNumber(inputs.monthlyContribution.value, 30000));
      var initialAmount = Math.max(0, toNumber(inputs.initialAmount.value, 0));

      var result = calculateSeries(years, annualRate, monthlyContribution, initialAmount);

      summary.finalAsset.textContent = formatJPY(result.finalAsset, currencySymbol);
      summary.totalInvested.textContent = formatJPY(result.totalInvested, currencySymbol);
      summary.totalProfit.textContent = formatJPY(result.totalProfit, currencySymbol);

      var options = {
        chart: {
          type: 'area',
          height: 360,
          toolbar: { show: false },
          fontFamily: 'Helvetica, sans-serif'
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
            name: labels.assetSeries || 'Asset',
            data: result.assetData
          },
          {
            name: labels.investedSeries || 'Invested Principal',
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
