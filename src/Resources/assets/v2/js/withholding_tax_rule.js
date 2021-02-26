$(document).ready(function() {
    refreshMasonry();
    const $enabledRules = $('.masonry div.panel');
    const $taxTypeSelect = $('#tax-type-select').selectize()[0].selectize;
    const $provinceTypeSelect = $('#province-select').selectize()[0].selectize;
    const $searchBox = $('#search-input');

    bindTaxTypeFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox);
    bindProvinceFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox);
    bindSearchBoxFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox);

});

function refreshMasonry() {
    $('.masonry').masonry();
}

function bindTaxTypeFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox) {
    $taxTypeSelect.on('change', function(taxType) {
        if (taxType !== '0') {
            $provinceTypeSelect.setValue(0);
        }
        const provinceId = $provinceTypeSelect.getValue();
        const searchBoxValue = $searchBox.val();
        hideOrShowRulesByFilters($enabledRules, taxType, provinceId, searchBoxValue);
        refreshMasonry();
    });
}

function bindProvinceFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox) {
    $provinceTypeSelect.on('change', function(provinceId) {
        if (provinceId !== '0') {
            $taxTypeSelect.setValue(0);
        }
        const taxType = $taxTypeSelect.getValue();
        const searchBoxValue = $searchBox.val();
        hideOrShowRulesByFilters($enabledRules, taxType, provinceId, searchBoxValue);
        refreshMasonry();
    });
}

function bindSearchBoxFilter($enabledRules, $taxTypeSelect, $provinceTypeSelect, $searchBox) {
    $searchBox.on('input', function() {
        const taxType = $taxTypeSelect.getValue();
        const provinceId = $provinceTypeSelect.getValue();
        hideOrShowRulesByFilters($enabledRules, taxType, provinceId, $(this).val());
        refreshMasonry();
    });
}

function hideOrShowRulesByFilters($enabledRules, taxType, provinceId, searchBoxValue) {

    if (filtersAreEmpty(taxType, provinceId, searchBoxValue)) {
        $enabledRules.show();
        return;
    }

    provinceId = parseInt(provinceId, 10);
    searchBoxValue = searchBoxValue.toLowerCase();
    $enabledRules.each(function() {
        const $rule = $(this);
        const $ruleHeading = $rule.find('.panel-heading');
        if (filtersApply($ruleHeading, taxType, provinceId, searchBoxValue)) {
            $rule.show();
        } else {
            $rule.hide();
        }
    });
}

function filtersApply($ruleHeading, taxType, provinceId, searchBoxValue) {
    const ruleTaxType = $ruleHeading.data('tax-type');
    const ruleProvinceId = $ruleHeading.data('province-id');
    const ruleHeadingText = $ruleHeading.prop('innerText').toLowerCase();
    return (taxType === '0' || taxType === ruleTaxType) &&
        (provinceId === 0 || provinceId === ruleProvinceId) &&
        (searchBoxValue.length === 0 || ruleHeadingText.indexOf(searchBoxValue) !== -1);
}

function filtersAreEmpty(taxType, provinceId, searchBoxValue) {
    return (taxType === '0' || taxType.length === 0) &&
        (provinceId === 0 || provinceId.length === 0) &&
        (searchBoxValue.length === 0);
}