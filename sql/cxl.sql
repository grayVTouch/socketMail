drop table if exists `cs_credit_card_bill`;
create table if not exists `cs_credit_card_bill` (
  id int auto_increment not null ,
  mail_id varchar(500) comment '邮箱标识符，也可称为邮箱id，唯一，用于区分每条邮件' ,
  user_id int comment 'cs_user.uid' ,
  start_date date comment '账单开始日期' ,
  end_date date comment '账单结束日期,所在月份也是账单月份' ,
  last_date date comment '最后还款日，' ,
  bill_amount decimal(13 , 2) default 0 comment '账单金额' ,
  bank_code char(255) comment '银行对应的code，可选的 code 请在 cs_bank_code 中查看' ,
  user_mailbox_id int comment 'cs_user_mailbox.id' ,
  create_time datetime default current_timestamp comment '创建时间' ,
  update_time datetime default current_timestamp on update current_timestamp ,
  primary key (id)
) engine=innodb character set utf8 collate = utf8_general_ci comment '信用卡账单 by cxl';

drop table if exists `cs_user_mailbox`;
create table if not exists `cs_user_mailbox` (
  id int auto_increment not null ,
  user_id int comment 'cs_user.uid' ,
  mailbox_id int comment 'cs_mailbox.id' ,
  protocol char(255) comment 'cs_mailbox_config.protocol ，使用的协议：pop / imap 等' ,
  username char(255) comment '用户名（即：邮箱账号）' ,
  password char(255) comment '密码 or 授权码' ,
  create_time datetime default current_timestamp ,
  update_time datetime default current_timestamp on update current_timestamp ,
  primary key (id) ,
  unique key (username)
) engine=innodb character set utf8 collate = utf8_general_ci comment '用户邮箱 by cxl';

-- 新增一条李总的邮箱，用以测试用
insert into cs_user_mailbox (user_id , mailbox_id , protocol , username , password) values
(2 , 1 , 'pop' , 'jfeng.li@qq.com' , 'snpjaexgamdibijj');

drop table if exists `cs_mailbox_log`;
create table if not exists `cs_mailbox_log` (
  id int auto_increment not null ,
  user_mailbox_id int comment 'cs_user_mailbox.id' ,
  bank_code char(255) comment '银行代码' ,
  bank_name varchar(500) comment '银行名称' ,
  `from` varchar(255) comment '发件人' ,
  content longtext comment '导致解析失败html' ,
  mail_id varchar(500) comment '邮箱标识符' ,
  `count` int default 0 comment '错误次数' ,
  create_time datetime default current_timestamp ,
  primary key (id)
) engine=innodb character set = utf8 collate = utf8_general_ci comment '信用卡账单解析失败日志 by cxl';

drop table if exists `cs_mailbox`;
create table if not exists `cs_mailbox` (
  id int auto_increment not null ,
  name char(255) comment '邮箱名称，比如 qq/126 ...' ,
  thumb varchar(500) comment '邮箱封面' ,
  create_time datetime default current_timestamp ,
  primary key (id)
) engine=innodb character set = utf8 collate = utf8_general_ci comment '支持的邮箱列表 by cxl';

drop table if exists `cs_mailbox_config`;
create table if not exists `cs_mailbox_config` (
  id int auto_increment not null ,
  mailbox_id int comment 'cs_mailbox.id' ,
  protocol char(255) comment '支持的协议有：pop|imap' ,
  server char(255) comment '类似 pop.126.com' ,
  port char(32) comment '端口号：无 ssl 加密' ,
  ssl_port char(32) comment '端口号：ssl 加密' ,
  create_time datetime default current_timestamp ,
  primary key (id) ,
  unique index (mailbox_id , protocol)
) engine=innodb character set = utf8 collate = utf8_general_ci comment '邮箱对应的配置文件 by cxl';

-- 默认增加的内容
insert into cs_mailbox (name) values
('qq') ,
('126');

insert into cs_mailbox_config (mailbox_id , protocol , server , ssl_port) values
(1 , 'pop' , 'pop.qq.com' , '995') ,
(1 , 'imap' , 'imap.qq.com' , '993') ,
(2 , 'pop' , 'pop.126.com' , '99');