/**
 * Created by negm on 06/10/16.
 */

var postModule = (function () {

    var postId = -1; //-1 undefined, 0 new, else id of post

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
        }).fail(function (xhr) {
            var response = JSON.parse(xhr.responseText);
            if (response.error) {
                alert(response.error);
            } else {
                alert("Post view has error, Try again later");
            }
        });
    }

    function validPost(data) {
        /**
         * check the user is not Empty
         * check the user is Numeric
         * Check The post content is not Empty
         * Check the post content is more than 100 characters
         * then return and Object has the status and/or the Errors
         */

        var valid = true;
        var errors = [];

        if (data.user == null || data.user === '') {
            valid = false;
            errors.push("User must not be empty");
        } else if (data.user != parseInt(data.user, 10)) {
            valid = false;
            errors.push("User is invalid");
        }

        if (data.content == null || data.content === '') {
            valid = false;
            errors.push("Content must not be empty");
        } else {
            var str = data.content.replace(/\s+/g, ' ');
            if (str.length < 100) {
                valid = false;
                errors.push("Content length must be more than 100 characters, current length is " + str.length);
            }
        }

        return {'status': valid, errors: errors};
    }

    function addNewPost(post, callback) {
        $.post('/api/posts/new', post, function (response) {
            if (response.msg == "OK") {
                var id = response.id;
                if ($.isFunction(callback)) {
                    callback(id);
                }
            }
        }).fail(function (xhr) {
            var response = JSON.parse(xhr.responseText);
            if (response.error) {
                alert(response.error);
            } else {
                alert("Post insertion failed, Try again later");
            }
        });
    }

    function editPost(post, callback) {
        $.ajax({
            url: '/api/posts/edit/' + post.id,
            type: 'PUT',
            data: post,
            success: function (response) {
                if (response.msg == "OK") {
                    var id = response.id;
                    if ($.isFunction(callback)) {
                        callback(id);
                    }
                }
            }
        }).fail(function (xhr) {
            var response = JSON.parse(xhr.responseText);
            if (response.error) {
                alert(response.error);
            } else {
                alert("Post updating failed, Try again later");
            }
        });
    }

    function addActions() {
        //To have the Events of elements

        $('#controlPostModal').on('hidden.bs.modal', function () {
            postId = -1;
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
                postId = 0;

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
                    postId = post.rowid;
                    console.log(postId);

                    $("#controlPostModal").modal('show');
                });

            });
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
                    url: '/api/posts/delete/' + id,
                    type: 'DELETE',
                    success: function (data) {
                        if (data.msg == "OK") {
                            alert("The Post Delete Successfully");

                            //if the deletion from View Page to redirect to the home page
                            if (viewPage == 1) {
                                location.href = "/";
                            } else {
                                //if from any list page just remove it from the DOM
                                var rowElement = deleteBtn.parent().parent().parent();
                                rowElement.fadeOut(600, function () {
                                    $(this).remove();
                                })
                            }
                        } else {
                            alert("Post deleting failed, Try again later");
                        }

                    }
                }).fail(function (xhr) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        alert(response.error);
                    } else {
                        alert("Post deleting failed, Try again later");
                    }
                });
            }

        });

        $("#postSaveBtn").on('click', function () {
            /**
             * check if the post come from the modal by the right way
             * collect the data from inputs
             * check validations
             * if it's a new post send then go to add post
             * else it's an edition for exist post go to update it
             * then alert the user by the results
             * go to the post page
             */

            if (postId == -1) {
                alert("There is some error, please try again later");
                return false;
            }

            var post = {};
            post.user = $("#userInput").val();
            post.content = $("#contentInput").val();

            var validation = validPost(post);
            if (validation.status == false) {
                var errorHTML = "";
                $.each(validation.errors, function (i, error) {
                    errorHTML += "<li>" + error + "</li>";
                });

                $("#postModalErrorsMsg ol").html(errorHTML);
                $("#postModalErrorsMsg").show();
            } else {
                $("#postModalErrorsMsg").hide();

                if (postId == 0) {
                    //it's new Post
                    addNewPost(post, function (id) {
                        alert("Post has been created successfully");
                        location.href = "/posts/" + id;
                    });
                } else if (postId != -1) {
                    //it's edition for exist post;
                    post.id = postId;
                    editPost(post, function (id) {
                        alert("Post has been edited successfully");
                        location.href = "/posts/" + id;
                    })
                }
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
    postModule.init();
});