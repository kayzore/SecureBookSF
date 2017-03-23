$(document).ready(function () {
    var client = new Faye.Client('http://localhost:3000/');

    client.subscribe('/' + $('#username').text(), function (message) {
        var type;
        if (message.type = 'like') {
            type = '<i class="fa fa-thumbs-up" aria-hidden="true"></i> ';
        } else if (message.type = 'comment') {
            type = '<i class="fa fa-commenting-o" aria-hidden="true"></i> ';
        } else {
            type = '';
        }
        $.notify({
            message: type + message.text
        }, {
            type: "info",
            allow_dismiss: true,
            delay: 3000,
            animate: {
                enter: 'animated fadeInLeft',
                exit: 'animated fadeOutLeft'
            },
            placement: {
                from: "bottom",
                align: "left"
            }
        });
    });

    $(".autogrow").autoGrow();

    $('#btnAddPictures').click(function () {
        $("#sb_activity_image_file").click();
    });

    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#image-preview-input')
                    .attr('src', e.target.result)
                    .removeClass('hidden')
                ;
            };

            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#sb_activity_image_file").change(function(){
        readURL(this);
    });

    $(document).on('click', '.btn-like', function(){
        var btn = this,
            id_activity = this.parentNode.parentNode.parentNode.dataset.activity;
        $(btn).append('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        $.ajax({
            url: Routing.generate('sb_activity_like'),
            method: 'post',
            data: {id_activity: id_activity},
            dataType: 'json',
            success: function (result) {
                var likes_text;
                if (result.activity_likes > 2) {
                    likes_text = result.activity_likes + " J'aimes";
                } else {
                    likes_text = result.activity_likes + " J'aime";
                }
                $(btn)
                    .html(likes_text)
                    .removeClass('btn-like')
                    .removeClass('btn-primary')
                    .addClass('btn-dislike')
                    .addClass('btn-success')
                ;
            }
        });
    });
    $(document).on('click', '.btn-dislike', function(){
        var btn = this,
            id_activity = this.parentNode.parentNode.parentNode.dataset.activity;
        $(btn).append('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        $.ajax({
            url: Routing.generate('sb_activity_dislike'),
            method: 'post',
            data: {id_activity: id_activity},
            dataType: 'json',
            success: function (result) {
                var likes_text;
                if (result.activity_likes > 2) {
                    likes_text = result.activity_likes + " J'aimes";
                } else {
                    likes_text = result.activity_likes + " J'aime";
                }
                $(btn)
                    .html(likes_text)
                    .removeClass('btn-dislike')
                    .removeClass('btn-success')
                    .addClass('btn-like')
                    .addClass('btn-primary')
                ;
            }
        });
    });

    $(document).on('click', '.btn-show-comments', function () {
        var comments_block = $(this.parentNode.parentNode.parentNode).next(),
            id_activity;
        if (comments_block.hasClass('open')) {
            comments_block.slideUp('slow');
        } else {
            comments_block.html('<p class="text-center">Chargement en cours <i class="fa fa-spinner fa-pulse fa-fw"></i></p>');
            comments_block.slideDown('slow');
            id_activity = this.parentNode.parentNode.parentNode.parentNode.dataset.activity;

            $.ajax({
                url: Routing.generate('sb_activity_get_comments'),
                method: 'post',
                data: {id_activity: id_activity},
                dataType: 'html',
                success: function (comments) {
                    comments_block.html(comments);
                }
            });
        }
        comments_block.toggleClass('open');
        comments_block.toggleClass('is_hidden');
    });
    $(document).on('click', '.btn-add-comment', function () {
        var btn_send = this,
            id_activity = this.parentNode.parentNode.dataset.activity,
            comment_text = $(this.previousElementSibling.previousElementSibling).val();

        $(btn_send).append('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        $.ajax({
            url: Routing.generate('sb_activity_add_comment'),
            method: 'post',
            data: {comment_text: comment_text, id_activity: id_activity},
            dataType: 'json',
            success: function (result) {
                console.log(result)
            }
        });
    });
});