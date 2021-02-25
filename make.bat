@echo off

docker run --rm ^
    -v "%CD%:/pwd" ^
    -v //var/run/docker.sock:/var/run/docker.sock ^
    -v //usr/bin/docker:/usr/bin/docker ^
    javanile/pwd > .pwd.var

set /p PWD=<.pwd.var

docker run -it --rm ^
    -w "%PWD%" ^
    -v "%CD%:%PWD%" ^
    -v //var/run/docker.sock:/var/run/docker.sock ^
    -v //usr/bin/docker:/usr/bin/docker ^
    javanile/make.bat %*
