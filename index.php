<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIRTUAL OFFICE</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- sweet alert pop-up  -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.10/dist/sweetalert2.all.min.js"></script>

    <!-- custom css file link  -->
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    <link rel="icon" href="images/flder.png">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700&display=swap');

            :root{
                --main-color:#d3ad7f;
                --black:#13131a;
                --bg:#010103;
                --border:.1rem solid rgba(255,255,255,.3);
            }

            *{
                font-family: 'Roboto', sans-serif;
                margin:0; padding:0;
                box-sizing: border-box;
                outline: none; border:none;
                text-decoration: none;
                /* text-transform: capitalize; */
                transition: .2s linear;
            }

            html{
                font-size: 62.5%;
                overflow-x: hidden;
                scroll-padding-top: 9rem;
                scroll-behavior: smooth;
            }

            html::-webkit-scrollbar{
                width: .8rem;
            }

            html::-webkit-scrollbar-track{
                background: transparent;
            }

            html::-webkit-scrollbar-thumb{
                background: #fff;
                border-radius: 5rem;
            }

            body{
                background: var(--bg);
            }

            section{
                padding:2rem 7%;
            }

            .heading{
                text-align: center;
                color:#fff;
                text-transform: uppercase;
                padding-bottom: 3.5rem;
                font-size: 4rem;
            }

            .heading span{
                color:var(--main-color);
                text-transform: uppercase;
            }

            .btn{
                margin-top: 1rem;
                display: inline-block;
                padding:.9rem 3rem;
                font-size: 1.7rem;
                color:#fff;
                background: #A52A2A;
                cursor: pointer;
                border-radius: 5px;
            }

            .btn:hover{
                letter-spacing: .2rem;
            }

            p{
                font-family: 'Bahnschrift SemiBold'; 
            }

            .header{
                background: var(--bg);
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding:1.5rem 7%;
                border-bottom: var(--border);
                position: fixed;
                top:0; left: 0; right: 0;
                z-index: 1000;
            }

            .header .logo img{
                height: 6rem;
            }

            .header .icons div{
                color:#fff;
                cursor: pointer;
                font-size: 2.5rem;
                margin-left: 2rem;
            }

            .header .icons div:hover{
                color:var(--main-color);
            }

            #menu-btn{
                display: none;
            }

            #search-btn{
                margin-left: 4px;
            }

            .home{
                min-height: 100vh;
                display: flex;
                align-items: center;
                background:url("images/background.png") no-repeat;
                background-size: cover;
                background-position: center;
            }

            .home .content{
                max-width: 60rem;
            }

            .home .content h3{
                font-size: 6rem;
                /* text-transform: uppercase; */
                color:#010103;
            }

            .home .content p{
                font-size: 17px;
                font-weight: lighter;
                line-height: 1.8;
                padding:1rem 0;
                color:#010103;
            }


            /* media queries  */
            @media (max-width:991px){

                html{
                    font-size: 55%;
                }

                .header{
                    padding:1.5rem 2rem;
                }

                section{
                    padding:2rem;
                }

            }

            @media (max-width:768px){

                #menu-btn{
                    display: inline-block;
                }

                .header .navbar{
                    position: absolute;
                    top:100%; right: -100%;
                    background: #fff;
                    width: 30rem;
                    height: calc(100vh - 9.5rem);
                }

                .header .navbar.active{
                    right:0;
                }

                .header .navbar a{
                    color:var(--black);
                    display: block;
                    margin:1.5rem;
                    padding:.5rem;
                    font-size: 2rem;
                }

                .header .search-form{
                    width: 90%;
                    right: 2rem;
                }

                .home{
                    background-position: left;
                    justify-content: center;
                    text-align: center;
                }

                .home .content h3{
                    font-size: 4.5rem;
                }

                .home .content p{
                    font-size: 1.5rem;
                }

            }

            @media (max-width:450px){

                html{
                    font-size: 50%;
                }

            }

            .swal2-popup {
                    font-size: 1.6rem !important;
                    font-family: 'Bahnschrift SemiBold';
                }

            .swal2-icon {
                    /* font-size: 12px; */
                }

            .swal2-.swal2-image{
                margin-top: 1px;
            }
    </style>
</head>
<body>
    
<!-- header section starts  -->

<header class="header">

    <a href="#" class="logo">
        <img src="images/logo.png" alt="">
    </a>

    <nav class="navbar">
        <!-- <a href="#home">home</a> -->
    </nav>

    <div class="icons">
        <a href="FileManager.php"><div class="fas fa-book" id="search-btn"></div></a>
        <a href="https://calendar.google.com/calendar/u/0/r?tab=kc&pli=1"><i class="fa fa-calendar" style="font-size:28px;color:white" id="search-btn"></i></a>
    </div>

</header>

<!-- header section ends -->

    <!-- home section starts  -->

    <section class="home" id="home">

        <div class="content">
            <h3>VIRTUAL OFFICE OF SPb</h3>
            <p>This VIRTUAL OFFICE is created to store all the document, it has been created by Nazihah Kail to Pn.Rosnani binti Mat Noor.</p>
            <a href="#" class="btn" onclick="NextPage()">Let's Go!</a>
        </div>

    </section>

    <!-- custom js file link  -->
    <script>
        function NextPage(){
            Swal.fire({
            title: 'WELCOME TO VIRTUAL OFFICE OF SPb!',
            imageUrl: 'images/logo2.png',
            imageWidth: 350,
            imageHeight: 250,
            imageAlt: 'Custom image',
            buttons: true,
            confirmButtonText: "CONTINUE!",
            showCancelButton: true,
            }).then((result) => {
            if (result.isConfirmed) {
                window.location = "FileManager.php";
            } 
            })
        }
    </script>

</body>
</html>