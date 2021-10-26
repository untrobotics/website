
<?php
require('../template/top.php');
head('My Profile', true, true);
?>
<head>
    <style>
        .profileName{
            text-align: center;
            font-family: Ubuntu;
            font-style: normal;
            font-weight: bold;
            font-size: 48px;
            line-height: 55px;
            color: #18AC5C;
        }
        .profileText{
            text-align: center;
            font-family: Ubuntu;
            font-style: normal;
            font-weight: normal;
            font-size: 24px;
            line-height: 28px;
            color: #000000;
        }
        .center {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 50%;
        }
        .button{
            display: inline-block;
            background: #007A38;
            border-radius: 6px;
            padding: 5px;
            text-align: center;
            font-family: Ubuntu;
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            color: #FFFFFF;
        }
        .orangeBox{
            background: #EDEDED;
            border-radius: 30px;
        }
    </style>
</head>
<section>
<main class="page-content">
    <div class="row">
        <div class="col-md-2 col-xs-0"></div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <h2 style = "text-align: center">MY PROFILE</h2></div>
                <div class="col-md-3"></div>
            </div>
            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-4"><img width = 300 class = "center" src="/images/bio-pics/temp-pic.jpg"></div>
                <div class="col-md-4"></div>
            </div>
            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-4">
                    <h3 class="profileName"><?php echo $userinfo['name']; ?></h3>
                </div>
                <div class="col-md-4"></div>
            </div>
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-8"><textarea rows = "4" style = "width: 100%"></textarea></div>
                <div class="col-md-2"></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h4 class = "profileText" style = "text-align:right">Email:</h4>
                </div>
                <div class="col-md-4">
                    <h3 class = "profileText"><?php echo $userinfo['email']; ?></h3>
                </div>
                <div class="col-md-4"><h3 class = "button">Edit</h3></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h4 class = "profileText" style = "text-align:right">Password:</h4>
                </div>
                <div class="col-md-4">
                    <h3 class = "profileText">**********</h3>
                </div>
                <div class="col-md-4"><h3 class = "button">Edit</h3></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h4 class = "profileText" style = "text-align:right">Phone Number:</h4>
                </div>
                <div class="col-md-4">
                    <h3 class = "profileText"><?php echo $userinfo['phone']; ?></h3>
                </div>
                <div class="col-md-4"><h3 class = "button">Edit</h3></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h4 class = "profileText" style = "text-align:right">Discord Account:</h4>
                </div>
                <div class="col-md-4">
                    <h3 class = "profileText"><?php echo $userinfo['discord']; ?></h3>
                </div>
                <div class="col-md-4"><h3 class = "button">Edit</h3></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h4 class = "profileText" style = "text-align:right">LinkedIn Account:</h4>
                </div>
                <div class="col-md-4">
                    <h3 class = "profileText"><?php echo $userinfo['linkedin']; ?></h3>
                </div>
                <div class="col-md-4"><h3 class = "button">Edit</h3></div>
            </div>
            <div class="row">
                <div class="col-md-12 orangeBox">
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class = "profileText" style = "text-align:right">UNT EUID:</h4>
                        </div>
                        <div class="col-md-4">
                            <h4 class = "profileText" style = "text-align:right"><?php echo $userinfo['euid']; ?></h4>
                        </div>
                        <div class="col-md-4">
                            <h3 class = "button">Edit</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class = "profileText" style = "text-align:right">Graduation Date:</h4>
                        </div>
                        <div class="col-md-4">
                            <h4 class = "profileText" style = "text-align:center">Spring 2023</h4>
                        </div>
                        <div class="col-md-4">
                            <h3 class = "button">Edit</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"><br></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <h4 class = "profileText" style = "text-align:right">CampusLabs Member</h4>
                        </div>
                        <div class="col-md-3">
                            <input type="checkbox"  name="campusLabsBox" value="campusLabs Member">
                        </div>
                        <div class="col-md-3">
                            <h4 class = "profileText" style = "text-align:right">Good Standing</h4>
                        </div>
                        <div class="col-md-3">
                            <input type="checkbox"  name="campusLabsBox" value="Good Standing">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <h3 class = "button">Order History</h3>
                </div>
                <div class="col-md-4">
                    <h3 class = "button">Payment</h3>
                </div>
                <div class="col-md-4">
                    <h3 class = "button">Dues</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-xs-0"></div>
    </div>
</main>

<?php
footer();
?>