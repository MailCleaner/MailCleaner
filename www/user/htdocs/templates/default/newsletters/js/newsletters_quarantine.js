$(function() {
    /**
     * Allow selected elements
     */
    $('.allow').on('click', function(event) {
            event.preventDefault();

            $.ajax({
                    async: true,
                    type: "get",
                    url: 'users/newsletters/allow',
                    dataType: "json",
                    data: {id:$(this).data('id')},
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
