document.addEventListener("DOMContentLoaded", () => {
  const roiCalculator = document.querySelector('.roi-calculator');
  if (!roiCalculator) return;

  const form = document.getElementById("roi-calculator-form");
  const resultsContainer = document.getElementById("roi-calculator-results");
  const resultsDivAnnual = document.getElementById("roi-results-annual");
  const resultsDivPowerplant = document.getElementById("roi-results-powerplant");
  const resultsDivSavings = document.getElementById("roi-results-savings");
  const resultsDivReturn = document.getElementById("roi-results-return");

  form.addEventListener("submit", (event) => {
    event.preventDefault(); // Prevent default form submission behavior

    const formData = new FormData(form);

    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    // Get the data-value attribute from the selected option in the region select
    const regionSelect = document.getElementById("region");
    const selectedOption = regionSelect.selectedOptions[0];
    const regionDataValue = selectedOption.getAttribute("data-value");

    // Perform calculations
    const monthlyConsupmtion = parseFloat(data["monthly_spendings"]);
    const annualCost = monthlyConsupmtion * 12;
    const annualCostWithInstalledPowerplant = annualCost * 0.09;
    const annualSavings = annualCost - annualCostWithInstalledPowerplant;

    // Calculating the power plant price

    // Hi and Low tariff
    const hiRateSm = 0.14049;
    const loRateSm = 0.073196;
    const hiRateLg = 0.177885;
    const loRateLg = 0.120747;

    let monthlyConsupmtionLg, monthlyConsupmtionSm;

    if (monthlyConsupmtion > 70) {
      monthlyConsupmtionSm = 70;
      monthlyConsupmtionLg = monthlyConsupmtion - 70;
    } else {
      monthlyConsupmtionSm = monthlyConsupmtion;
      monthlyConsupmtionLg = 0;
    }

    const taxLow = monthlyConsupmtionSm / 1.13;
    const taxHi = monthlyConsupmtionLg / 1.13;

    const tariffSmHi = taxLow * 0.85;
    const tariffSmLow = taxLow * 0.15;
    const tariffLgHi = taxHi * 0.85;
    const tariffLgLow = taxHi * 0.15;

    const kwhjSmHi = tariffSmHi / hiRateSm;
    const kwhjSmLow = tariffSmLow / loRateSm;
    const kwhjLgHi = tariffLgHi / hiRateLg;
    const kwhjLgLow = tariffLgLow / loRateLg;

    const totalAnnualConsumption = (kwhjSmHi + kwhjSmLow) * 12 + (kwhjLgHi + kwhjLgLow) * 12;
    const regionCoefficient = regionDataValue;
    const plantPower = (totalAnnualConsumption * regionCoefficient) / 1000;
    const plantPrice = plantPower * 1000;
    const roi = plantPrice / annualSavings;

    const roiDecimal = roi; 

    // Convert the ROI to years and months
    const roiYears = Math.floor(roiDecimal);
    const roiMonths = Math.round((roiDecimal - roiYears) * 12);

    const resultsAnnual = `${annualCost.toFixed(2)}`;
    const resultsPowerplant = `${annualCostWithInstalledPowerplant.toFixed(2)}`;
    const resultsAnnualSavings = `${annualSavings.toFixed(2)}`;
    const resultsReturn = roiMonths > 0 ? `${roiYears} godina i ${roiMonths} mj` : `${roiYears} godina`;

    resultsDivAnnual.innerHTML = resultsAnnual;
    resultsDivPowerplant.innerHTML = resultsPowerplant;
    resultsDivSavings.innerHTML = resultsAnnualSavings;
    resultsDivReturn.innerHTML = resultsReturn;
    resultsContainer.scrollIntoView({ behavior: "smooth" });

    // Retain form data
    for (const key in data) {
      const field = form.querySelector(`[name=${key}]`);
      if (field.type === "checkbox") {
        field.checked = data[key] === "1";
      } else {
        field.value = data[key];
      }
    }

    // Add calculated results to the data object
    data['resultsAnnual'] = resultsAnnual;
    data['resultsPowerplant'] = resultsPowerplant;
    data['resultsAnnualSavings'] = resultsAnnualSavings;
    data['resultsReturn'] = resultsReturn;


    // Send data to the server
    fetch(roiCalculatorAjax.ajaxurl, {
      method: 'POST',
      body: new URLSearchParams({
          action: 'handle_form_submission',
          ...data
      }),
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
      },
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('Form submitted successfully');
        } else {
            console.error('Form submission failed:', result.data);
        }
    })
    .catch(error => console.error('Error:', error));
  
  });
});
