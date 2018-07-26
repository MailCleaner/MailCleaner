$(function() {
    /**
     * Allow selected elements
     */
    $('.allow').on('click', function(event) {
            event.preventDefault();
            $.ajax({
                    async: true,
                    type: "get",
                    url: 'newsletters.php',
                    dataType: 'html',
                    data: { id: $(this).data('id'), a: $(this).data('a') },
                    success : function(data) {		
                    $.ajax({
                            async: true,
                            type: "get",
                            url: 'fm.php',
                            dataType: "html",
                            data: {a:data.username, id:data.id, s:data.storage},
                            success : function(data) {		
                                    location.reload();
                            }
                        });
                    }
            });
    });
});
