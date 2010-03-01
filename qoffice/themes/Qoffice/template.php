<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <?php gpOutput::GetHead(); ?>
    </head>
    <body>
        <div id="conteiner">
            <div id="header">
                <div class="logo"><a href="/"><img src="/themes/Qoffice/images/logo.jpg" alt="דף הבית" title="דף הבית" /></a></div>
                <div class="menu">
                    <?php
		                $GP_ARRANGE = false;
		                gpOutput::Get('Menu');
		            ?>
                </div>
            </div>
            <div id="content">
                <div class="text">
                    <?php $page->GetContent() ?>
                </div>   
            </div>
            <div id="footer">
                <div class="slogan">

                    <h2><i>ההצלחה שלנו היא החיסכון שלך!</i></h2>
                </div>
                <div class="pic"><img src="/themes/Qoffice/images/people.png"/></div>
            </div>
            <div id="info">
                <?php $page->GetExtra( 'Footer' ); ?>
            </div>
            <?php gpOutput::GetAdminLink(); ?>
        </div>
    </body>

</html>
