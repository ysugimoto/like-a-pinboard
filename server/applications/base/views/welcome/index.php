<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

    <title>Seezoo-Framework :: Application-oriented framework</title>
    <link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=latin,cyrillic-ext' rel='stylesheet' type='text/css'>
    <style type="text/css">
      ::selection{ background-color: #E13300; color: white; }
      ::moz-selection{ background-color: #E13300; color: white; }
      ::webkit-selection{ background-color: #E13300; color: white; }
      
      body { font-family: 'Ubuntu', sans-serif; color: #333; letter-spacing: 1px; }
      .box { width: 800px; margin: 0 auto; padding: 10px 10px 50px 10px; background-position: top right; background-repeat: no-repeat; }
      .box { background-image : url(data:image/png;base64,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);}
      h1 { color: #333; text-align: right; margin-right: 10px;} 
      h2 { color: #666; font-size: 16px; margin-top: -20px; text-align: right; margin-bottom: 30px; margin-right: 10px; }
      h3 { border-bottom: solid 1px #ccc; padding-bottom: 5px; margin-bottom: 30px; }
      p { font-size: 0.9em; }
      code { border: solid 1px #D0D0D0; padding: 10px; margin: 20px; display: block; background-color: #F9F9F9; font-style: italic; font-size: 12px; }
      strong { color: #E13300; }
      p a { text-decoration: none; color: #266DD2; }
      p a:hover { text-decoration: underline; }
    </style>
  </head>

  <body>
    <div class="box">
    <h1>Seezoo-Framework</h1>
    <h2>An application-oriented framework</h2>
    
      <h3>Working environment</h3>
      <p>This page has been called from the following controllers:</p>
      <code><?php echo Seezoo::getInstance()->router->getInfo('loadedFile');?></code>
      <p>This page has been rendered from the following view file:</p>
      <code><?php echo Application::get()->path;?>views/welcome/index.php</code>
      <p>The state of the process is as follows (Can be changed in the config) :</p>
      <code>
Working application name : <strong><?php echo Application::get()->name;?></strong><br />
View rendering engine:     <strong><?php echo get_config('rendering_engine');?></strong>
      </code>
      <p>This project hosted on <a href="#">GitHub</a></p>
    </div>
  </body>
</html>
