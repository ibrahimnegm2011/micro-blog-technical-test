/**
 * Created by negm on 06/10/16.
 */

var blogModule = (function () {

    var blogId = -1; //-1 undefined, 0 new, else id of post
    function fillUserDropdownList(callback) {
        $.get('/api/users', function (data) {
            $("#userInput").html('<option value="">Select User</option>');
            $.each(data, function (i, user) {
                var opt = $('<option />')
                    .text(user.name)
                    .val(user.user_id);

                $("#userInput").append(opt);
            });

            if ($.isFunction(callback)) {
                callback();
            }
        });
    }

    function getPostData(id, callback) {
        $.get('/api/posts/id/' + id, function (post) {
            if ($.isFunction(callback)) {
                callback(post);
            }
        });
    }

    function addActions() {
        //To have the Events of elements

        $('#controlPostModal').on('hidden.bs.modal', function () {
            blogId = -1;
        });

        $("#createPostBtn").on('click', function () {
            /**
             * fill the user dropdown
             * empty the inputs
             * show the modal
             */
            fillUserDropdownList(function () {
                $("#userInput").val("");
                $("#contentInput").html("");
                blogId = 0;

                $("#controlPostModal").modal('show');
            });
        });

        $(".editPostBtn").on('click', function () {
            /**
             * fill the user dropdown
             * get post data
             * set the old post data to inputs
             * show the modal
             */
            var id = $(this).data('rowid');
            fillUserDropdownList(function () {
                getPostData(id, function (post) {
                    $("#userInput").val(post.user_id);
                    $("#contentInput").html(post.content);
                    blogId = post.rowid;
                    console.log(blogId);

                    $("#controlPostModal").modal('show');
                });

            });

            $("#userInput").val("");
            $("#contentInput").html("");

            $("#controlPostModal").modal('show');
        });

        $(".deletePostBtn").on('click', function () {
            /**
             * take the confirmation from the user
             * request to delete the post
             * if success remove the post block
             * else alert user
             */
            if (confirm('Are you sure you want to delete this post?')) {
                var id = $(this).data('rowid');
                var viewPage = $(this).data('viewpage');
                var deleteBtn = $(this);
                console.log(viewPage);
                $.ajax({
                    url: '/api/posts/delete/'+id,
                    type: 'DELETE',
                    success: function(data) {
                        if(data.msg=="OK") {
                            alert("The Post Delete Successfully");

                            //if the deletion from View Page to redirect to the home page
                            if(viewPage == 1){
                                location.href = "/";
                            }else{
                                //if from any list page just remove it from the DOM
                                var rowElement = deleteBtn.parent().parent().parent();
                                rowElement.fadeOut(600, function(){
                                    $(this).remove();
                                })
                            }
                        }

                    }

                });
            }

        });
    }

    return {
        init: function () {
            addActions();
        }
    }
})();

$(document).ready(function () {
    blogModule.init();
});