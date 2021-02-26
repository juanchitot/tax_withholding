require('bootstrap-sass/assets/javascripts/bootstrap.min');
require('../../../../CommonBackBundle/Resources/assets/js/bootstrap-datepicker.min.js');

import currentLanguage from './lang';
$.fn.dataTable.ext.errMode = 'none';

const addValidationError = (origin, message) => {
    const input = $(`#withholding_tax_certificates_filter_${origin}`);
    input.closest('.form-group').addClass('has-error');
    input.parent().after(`<span class="help-block input-error">${message}</span>`);
};

const removeValidationErrors = () => {
    const errorFormGroups = $('.has-error');
    errorFormGroups.removeClass('has-error');
    errorFormGroups.find('.help-block').remove();
};

const initDatepicker = (inputId) => {
    const $datepicker = $('input[id$="' + inputId + '"]').datepicker({
        format: 'mm/yyyy',
        viewMode: 1,
        minViewMode: 1,
    }).on('changeDate', (ev) => {
        $datepicker.setUTCDate(ev.date);
        $datepicker.hide()
    }).data('datepicker');
    $datepicker.setUTCDate(new Date());
    return $datepicker;
};

const validatePeriods = (periodFrom, periodTo) => {
    if (periodFrom.date.valueOf() > periodTo.date.valueOf()) {
        addValidationError('periodFrom', 'El periodo desde no puede ser mayor al hasta.');
        periodFrom.show();
        return false;
    }

    if (monthDiff(periodFrom.date, periodTo.date) > 12) {
        addValidationError('periodFrom', 'No es posible consultar un rango de periodos mayor a un año.');
        periodFrom.show();
        return false;
    }

    return true;
};

const formatDate = (date) => {
    const month = numberPad((date.getMonth() + 1));
    const year = date.getFullYear();
    return year + "-" + month + "-01";
}

const numberPad = (number) => {
    if (number < 10) {
        number = "0" + number;
    }
    return number;
}

const monthDiff = (dateFrom, dateTo) => {
    return dateTo.getMonth() - dateFrom.getMonth() + (12 * (dateTo.getFullYear() - dateFrom.getFullYear()));
}

const enableDisableZipButton = (shouldBeEnabled) => {
    const $zipBtn = $("#zip-btn");
    if (shouldBeEnabled) {
        $zipBtn.attr('disabled', false);
        $zipBtn.removeClass('btn-default').addClass('btn-success');
        $zipBtn.off().on('click', () => {
            window.open(getDownloadZipUrl(), '_blank');
        });
    } else {
        $zipBtn.attr('disabled', true);
        $zipBtn.removeClass('btn-success').addClass('btn-default');
        $zipBtn.off();
    }
}

const getDownloadZipUrl = () => {
    let url = downloadZipUrl;
    url = url.concat('?periodFrom=' + formatDate($datepickerFrom.date));
    url = url.concat('&periodTo=' + formatDate($datepickerTo.date));
    url = url.concat('&taxType=' + $('#withholding_tax_certificates_filter_taxType').val());
    return url;
}

const $datepickerFrom = initDatepicker('periodFrom');
const $datepickerTo = initDatepicker('periodTo');

const dataTable = $('#certificates-list').dataTable({
    ajax: {
        url: dataSource,
        data: {
            withholding_tax_certificates_filter: {
                periodFrom: () => formatDate($datepickerFrom.date),
                periodTo: () => formatDate($datepickerTo.date),
                taxType: () => $('#withholding_tax_certificates_filter_taxType').val(),
            }
        },
        error: (responseJSON) => {
            responseJSON.errors.forEach(({origin, message}) => {
                addValidationError(origin, message);
            });
            dataTable._fnProcessingDisplay(false);
        }
    },
    processing: true,
    serverSide: true,
    searching: false,
    language: {
        url: currentLanguage
    },
    order: [
        [0, "desc"]
    ],
    columns: [
        {data: 'period', className: 'text-center'},
        {data: 'type', className: 'text-center'},
        {data: 'province'},
        {data: 'fileName'},
        {data: 'status', className: 'text-center'},
        {data: 'actions', orderable: false, className: 'text-center'},
    ],
    drawCallback: function (settings) {
        const rowCount = this.api().rows({page:'current'}).count();
        enableDisableZipButton(rowCount);
    }
});

$('#search-btn').on('click', () => {
    removeValidationErrors();
    if (validatePeriods($datepickerFrom, $datepickerTo)) {
        dataTable.api().draw();
    }
});