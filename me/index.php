
<?php
require('../template/top.php');
head('My Profile', true, true);
?>
<head>
    <style>
        .ellipse{
            margin: 97px auto 40px auto;
            height: 294px;
            width: 294px;
            left: -1919px;
            top: -150px;
            border-radius: 50%;
            background: #C4C4C4;
        }
        .rectangle{
            position: absolute;
            width: 941px;
            height: 166px;

            border: 1px solid #B7B7B7;
            box-sizing: border-box;
            border-radius: 30px;
        }
        .email{
            /*position: absolute;*/
            width: 70px;
            height: 28px;

            /*font-family: Ubuntu;*/
            font-style: normal;
            font-weight: bold;
            font-size: 24px;
            line-height: 28px;
            /* identical to box height */
            color: #000000;
        }
        .editButton{
            position: absolute;
            width: 40px;
            height: 25px;

            /*font-family: Ubuntu;*/
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            line-height: 25px;

            color: #000000;
            
        }

    </style>
</head>

<main class="page-content">
    <!-- Classic Breadcrumbs-->
    <section class="section-50">
        <div class="shell text-sm-left">
            <div class="blog-post">
                <div class="range">
                    <div class="cell-md-preffix-2 cell-lg-9 cell-md-8 cell-xl-8">
                        <h1 style="text-align:center">My Profile</h1>
                        <div class="ellipse"></div>
                        <h3 style="text-align:center; color: #18AC5C; "><u>Jonathan Doe</u></h3>
                        <pre>Profile Info</pre>
                        <div class="email">Email</div>
                        <div class="editButton">Edit Button</div>
<!--                        <div class="rectangle"></div>-->
<!--                        <p>Profile info.</p>-->
                    </div>
                    <div class="cell-xs-12">
                        <div class="left-aside"><span class="small text-darker text-uppercase text-bold text-spacing-340"><?php echo $userinfo['name']; ?></span>
                            <div class="divider-custom veil reveal-md-block"></div>
                            <ul class="list text-md-center">
                                <li><a href="#" class="ioon icon-sm icon-darker fa-check"></a></li>
                                <li><a href="#" class="ioon icon-sm icon-darker fa-times"></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
footer();
?>