<?php
use Yab\Core\Logger;
use Yab\Core\Config;
use Yab\Core\Request;

$codeOffset = 10; 

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>

    <title><?php echo $title; ?></title>
    
    <style type="text/css">
    
        h1 {font-size: 18px; margin: 0px; padding: 0px; color: #ff6040}
        h2 {font-size: 14px; margin: 10px 0px; padding: 0px; color: #ffb000}
        h3 {font-size: 14px; text-decoration: underline}
        strong {font-size: 12px; text-decoration: underline}
        
        ul.tabs { padding: 0.5em ; border-bottom: 1px solid black; margin: 0; }
        li.tab { color: #1c66ad; display: inline; padding: 0.5em; margin: 0px; }
        li.tab a { color: #1c66ad; text-decoration: none; margin: 0px; font-weight: bold; }
        li.tab a:hiver { color: red; text-decoration: none; }
        
        div.tab {margin: 10px 0px; padding: 0px}
        div.tab a {text-decoration: none; color: #1c66ad}
        div.tab a:hover {text-decoration: underline}
        
        <?php if(count($traces)): ?>
        div.tab div.traceFile {background-color: #ffffcc; margin: 20px 0px }
        div.tab div.traceFile p {border-bottom: 1px solid #dddddd; margin: 0px; padding: 0px}
        div.tab div.traceFile p.current {background-color: #ff9966}
        div.tab div.traceFile span.lineNumber {display: inline-block; width: 35px; background-color: #cccccc; padding: 0 5px; margin-right: 5px;}
        div.tab div.traceFile span.current {background-color: #ff9966; border-right: 1px solid #dddddd;}
        div.tab div.traceFile span.comment {display: inline-block; color: #cccccc;}
        <?php endif; ?>

    </style>
    
    <script type="text/javascript">
        function hideAllTabs() {
            <?php if(count($traces)): ?>hideTab('traces');<?php endif; ?>
            hideTab('config');
            hideTab('request');
            hideTab('response');
            hideTab('session');
            hideTab('server');
            hideTab('debug');
        }
        function hideTab(id) {
            document.getElementById(id).style.display = 'none';
            document.getElementById(id + 'Tab').style.border  = '1px solid black';
            document.getElementById(id + 'Tab').getElementsByTagName('a')[0].style.color  = '#1c66ad';
        }
        function showTab(id) {
            hideAllTabs();
            document.getElementById(id).style.display = 'block';
            document.getElementById(id + 'Tab').style.borderBottom  = '1px solid white';
            document.getElementById(id + 'Tab').getElementsByTagName('a')[0].style.color  = 'red';
        }
    </script>
    
</head>
<body>

    <ul class="tabs">
        <?php if(count($traces)): ?><li class="tab" id="tracesTab"><a href="#traces" onclick="showTab('traces');" title="Toggle traces !">Traces</a></li><?php endif; ?>
        <li class="tab" id="configTab"><a href="#config" onclick="showTab('config');" title="Toggle config !">Config</a></li>
        <li class="tab" id="requestTab"><a href="#request" onclick="showTab('request');" title="Toggle request !">Request</a></li>
        <li class="tab" id="responseTab"><a href="#response" onclick="showTab('response');" title="Toggle response !">Response</a></li>
        <li class="tab" id="serverTab"><a href="#server" onclick="showTab('server');" title="Toggle server !">Server</a></li>
        <li class="tab" id="sessionTab"><a href="#session" onclick="showTab('session');" title="Toggle session !">Session</a></li>
        <li class="tab" id="debugTab"><a href="#debug" onclick="showTab('debug');" title="Toggle debug !">Debug</a></li>
    </ul>
    
    <div class="tab" id="debug" style="display: none">
        <?php foreach(Logger::getLoggers() as $name => $logger): ?>
        <ul>
            <li><?php echo 'ob_start opened: '.ob_get_level(); ?></li>
            <?php $this->partial('templates/dump.php', array('dumpTitle' => $name, 'data' => $logger->getDebugMessages())); ?>
        </ul>
        <?php endforeach; ?>
    </div>
    
    <div class="tab" id="server" style="display: none">
        <?php $this->partial('templates/dump.php', ['data' => ['_SERVER' => $_SERVER]]); ?>
    </div>
    
    <div class="tab" id="request" style="display: none">
        <?php $this->partial('templates/dump.php', [
            'data' => [
                'queryString' => $request->getQueryString(), 
                'queryParams' => $request->getQueryParams(), 
                'bodyParams' => $request->getBodyParams(), 
                'headers' => $request->getHeaders(),
                'cookies' => $request->getCookies(),
            ],
        ]); ?>
    </div>
    
    <div class="tab" id="response" style="display: none">
        <?php $this->partial('templates/dump.php', [
            'data' => [
                'code' => $response->getCode(),
                'headers' => $response->getHeaders(),
                'cookies' => $response->getCookiesHeaders(),
        ]]); ?>
    </div>
    
    <div class="tab" id="session" style="display: none">
        <?php $this->partial('templates/dump.php', ['data' => Request::getSession()]); ?>
    </div>
    
    <div class="tab" id="config" style="display: none">
        <?php $this->partial('templates/dump.php', array('data' => Config::getConfig()->getParams())); ?>
    </div>
    
    <?php if(count($traces)): ?>

    <div class="tab" id="traces" style="display: none;">
    <h1><?php echo $title; ?></h1>
    <h2><pre><?php echo $message; ?></pre></h2>
        
        <strong>Traces</strong> : 
    
        <?php foreach($traces as $index => $trace): ?>
        
        <div>
        
            <a href="#trace<?php echo $index; ?>" onclick="document.getElementById('trace<?php echo $index; ?>').style.display = document.getElementById('trace<?php echo $index; ?>').style.display != 'block' ?  'block' : 'none'; return false;" title="Toggle trace !">
                <?php if(isset($trace['file']) && $trace['file']): ?><?php echo $trace['file']; ?><?php else: ?>unknown<?php endif; ?>
                L: <?php if(isset($trace['line']) && $trace['line']): ?><?php echo $trace['line']; ?><?php else: ?>unknown<?php endif; ?>
            </a>
            
            <div id="trace<?php echo $index; ?>" style="padding-top: 10px; display: <?php echo $index === 0 ? 'block' : 'none'; ?>">

            <?php if(isset($trace['class']) && $trace['class']): ?>
                <strong>Class</strong> : <?php echo $trace['class']; ?><br />
            <?php endif; ?>
            
            <?php if($trace['function']): ?>
                <strong>Method</strong> : <?php echo $trace['function']; ?><br />
                
                <?php if(count($trace['args'])): ?>
                
                <?php foreach($trace['args'] as $i => $arg): ?>
                <strong>Arg <?php echo $i; ?></strong> : <pre><?php echo print_r($arg, 1); ?></pre>
                <?php endforeach; ?>
                
                <?php else: ?>
                <strong>No arg</strong><br />
                <?php endif; ?>
                
            <?php endif; ?>
        
            <?php if(isset($trace['file']) && $trace['file'] && isset($trace['line']) && $trace['line']): ?>
            
                <strong>Source file</strong> : 
                <div class="traceFile">
                
                <?php
                
                    if ($trace['function'] == '__construct')
                        $trace['function'] = $trace['class'];

                    $lines = file($trace['file']);

                    $start = max(0, $trace['line'] - $codeOffset - 1);
                    $offset = min(count($lines), $trace['line'] - $start + $codeOffset);

                    $lines = array_slice($lines, $start, $offset);
                    
                    foreach($lines as $number => $line): 
                    
                        $number = $start + $number + 1;

                        $current = (bool) ($number === $trace['line']);
                        
                    ?>
                        
                        <p<?php if($current): ?> class="current"<?php endif; ?>><span class="lineNumber<?php if($current): ?> current<?php endif; ?>"><?php echo $number; ?></span>
                        
                        <?php echo trim(preg_replace('~^<span[^>]+>(.*)</span>$~uis', '$1', trim(strip_tags(str_replace('&lt;?php&nbsp;', '', trim(highlight_string('<'.'?'.'php ' . $line, true))), '<span>')))); ?>
                        
                        </p>
                        
                    <?php endforeach; ?>

                </div>
            
            <?php endif; ?>
        
            </div>
            
        </div>
        
        <?php endforeach; ?>

    </div>

    <script type="text/javascript">showTab('traces');</script>

    <?php else: ?>

    <script type="text/javascript">showTab('config');</script>
    
    <?php endif; ?>
    
    
</body>
</html>