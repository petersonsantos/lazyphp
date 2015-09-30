prevent_ajax_view = true;

$(document).ready(function() {
    $('button[data-dismiss="modal"]').click(function() {
        //history.go(-1);
    });
    modallink = null;
    $(document).on('hidden.bs.modal', function(e) {
        $(e.target).removeData('bs.modal');
        $('.modal .modal-content').html(' ');
    });

    $('body').on('click', 'a[data-toggle=modal]', function (event) {
        event.preventDefault();
        modalharef = $(this).attr('href');
        if ($(this).attr('data-href')) {
            modalharef = $(this).attr('data-href');
            modalelement = this;
        }
        if (modalharef.indexOf("?") == -1)
            modalharef += '?'
        modalharef += '&ajax=true';
        $(this).attr('href', modalharef);
        if (!$(this).attr('data-target')) {
            $(this).attr('data-target', '#modal');
        }
    });
});
