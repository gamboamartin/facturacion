$(document).ready(function () {
    $('#tabla_empleados').DataTable({
        responsive: false,
        dom: 'Bfrtip',
        buttons: [
            'colvis'
        ],
        pageLength: 25,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        columnDefs: [
            { targets: [1,2,3,5,7,8,9,10,11,12,13], visible: false }
        ]
    });
});
