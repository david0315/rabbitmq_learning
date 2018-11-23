# rabbitmq 学习笔记

### 环境
```
版本
RabbitMQ 3.5.7, Erlang 18.3
PHP 7.2
vagrant Homestead 


php组件是 官方的php-amqplib，也是使用最广泛的一个组件包
composer require php-amqplib/php-amqplib

```
####闲谈
最近在学习rabbitmq 翻看了网上找了很多资料，关于php版本的内容特别少，
有很多是hello world级别的，对rabbitmq 介绍都太肤浅，只是简单的收发消息，
rabbitmq不仅仅是可以这样使用，还有很多其他的功能，查阅搜索了很多rabbitmq资料
再次感慨java的生态环境真是太好了，网上找到的资料 也大多数都是java的，
于是我一边学习java的资料 ，一遍写一个PHP版本的rabbitmq使用方式，
方便自己后续查阅，也希望可以帮到其他想使用rabbitmq的phper。
有不对的地方欢迎大家随时指正，1608085113@qq.com
 
