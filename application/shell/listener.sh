#!/bin/sh


###################################
###
###    进程守护
###
###################################
    source /etc/profile

     shellpath=$(cd `dirname $0`; pwd)
     time=$(date "+%Y-%m-%d %H:%M:%S")
     echo "${time}"
   #日志队列
    profitWorker=$(ps -ef | grep 'Profit.php' | grep -v 'grep' | wc -l)
    echo $profitWorker
    if [ $profitWorker -eq 0 ]
    then

        php $shellpath/Profit.php > /dev/null &

    fi




