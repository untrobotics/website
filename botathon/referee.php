<?php
require('../template/top.php');
head('Botathon referee Page', true);
?>

<style>
    .btn-group button {
        background-color: #4CAF50; /* Green background */
        border: 1px solid green; /* Green border */
        color: white; /* White text */
        padding: 10px 24px; /* Some padding */
        cursor: pointer; /* Pointer/hand icon */
        float: left; /* Float the buttons side by side */
    }

    .btn-group button:not(:last-child) {
        border-right: none; /* Prevent double borders */
    }

    /* Clear floats (clearfix hack) */
    .btn-group:after {
        content: "";
        clear: both;
        display: table;
    }

    /* Add a background color on hover */
    .btn-group button:hover {
        background-color: #3e8e41;
    }
    
    .button-section{
        max-resolution: 80px;
        margin: 10px;
    }

</style>

<main class="page-content">
<!-- Classic Breadcrumbs-->
<section class="breadcrumb-classic">
    <div class="rd-parallax">
        <div data-speed="0.25" data-type="media" data-url="/images/hackathon-demo.jpg" class="rd-parallax-layer"></div>
        <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
            <div class="shell">
                <ul class="list-breadcrumb">
                    <li><a href="/">Home</a></li>
                    <li>Botathon</li>
                </ul>
            </div>
        </div>
    </div>
</section>

    <div style = "text-align: center; margin-top: 40px">
        <form style = "display: inline-block;">
            <label for="teamName1">Team 1: </label><br>
            <input type="text" id="fname" name="fname"><br>
            <label for="teamName2">Team 1: </label><br>
            <input type="text" id="fname" name="fname"><br>
            <input type="submit" value="Submit">
        </form>
    </div>

    <div class = "button-section" style = "text-align: center; margin-top: 40px">
        <div class="btn-group" style = "display: inline-block;">
          <button>Start timer</button>
            <br><br><br>
          <button>Add point team 1</button>
            <button>Sub point team 1</button>
            <button>Reset team 1</button>
            <br><br><br>
            <button>Add point team 2</button>
          <button>Sub point team 2</button>
            <button>Reset team 2</button>
        </div>
    </div>



<?php
footer(false);
?>

<script>
    $("#botathon-navigation ul li a").click(function(e) {
        $("#botathon-navigation ul li.active").removeClass("active");
        $(this).parent().addClass("active");
    });

    $(document).ready(function(){
        // Add smooth scrolling to all links
        $("#botathon-navigation ul li a").on('click', function(event) {

            // Make sure this.hash has a value before overriding default behavior
            if (this.hash !== "") {
                // Prevent default anchor click behavior
                event.preventDefault();

                // Store hash
                var hash = this.hash;

                // Using jQuery's animate() method to add smooth page scroll
                // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
                $('html, body').animate({
                    scrollTop: $(hash).offset().top
                }, 800, function(){

                    // Add hash (#) to URL when done scrolling (default click behavior)
                    window.location.hash = hash;
                });
            } // End if
        });
    });
</script>
