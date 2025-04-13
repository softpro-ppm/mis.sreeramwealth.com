$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();

    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        if (!isValid) {
            e.preventDefault();
        }
    });

    // Date picker initialization
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Auto-format phone numbers
    $('input[type="tel"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 0) {
            value = value.match(new RegExp('.{1,10}'))[0];
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            $(this).val(value);
        }
    });

    // Auto-format currency inputs
    $('input[type="number"]').on('input', function() {
        let value = $(this).val();
        if (value) {
            value = parseFloat(value).toFixed(2);
            $(this).val(value);
        }
    });

    // Table search functionality
    $('#searchInput').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('.table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        let input = $(this).prev('input');
        let type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Dynamic form fields for policy types
    $('#policyType').on('change', function() {
        let type = $(this).val();
        $('.policy-type-fields').hide();
        $(`#${type}Fields`).show();
    });

    // Auto-calculate premium based on coverage amount
    $('#coverageAmount').on('input', function() {
        let coverage = parseFloat($(this).val()) || 0;
        let premium = coverage * 0.01; // 1% of coverage amount
        $('#premium').val(premium.toFixed(2));
    });

    // Handle file upload preview
    $('input[type="file"]').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#filePreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Initialize DataTables if present
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }
}); 