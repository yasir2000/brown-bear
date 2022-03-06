The Brown Bear Project
=======================

![Brown Bear logo](media/Screenshot_1.png "The Brown Bear Project")

Brown Bear is a project repo aims to provide "All in One" box for small and medium agile software startup, as most of SaaS startups I have seen in my area, always yearn to find one total solution for total Application Lifecycle Management, integrated with Git SCM (Source Code Management) as well as Issue Management and Code MR, PR full control, all integrated with full automated workflow 

Brown Bear عبارة عن مشروع ريبو يهدف إلى توفير حزمة "الكل في واحد" لبدء تشغيل البرامج الصغيرة والمتوسطة ، نظرًا لأن معظم الشركات الناشئة SaaS التي رأيتها في منطقتي ، تتوق دائمًا إلى إيجاد حل كامل لإدارة دورة حياة التطبيقات بالكامل ، ومتكاملة مع Git SCM (إدارة كود المصدر) بالإضافة إلى إدارة المشكلات و Code MR والتحكم الكامل في العلاقات العامة ، وكلها متكاملة مع سير عمل مؤتمت كامل

## Principles

Brown Bear is an open project guided by strong principles, aiming to be modular, flexible and without too strong an opinion on user experience.
It is open to the community to help set its direction.

- Modular: the project includes lots of components that have well-defined functions and APIs that work together.
- Batteries included but swappable: Brown Bear includes enough components to build fully featured container system, but its modular architecture ensures that most of the components can be swapped by different implementations.
- Usable security: Brown Bear provides secure defaults without compromising usability.
- Developer focused: The APIs are intended to be functional and useful to build powerful tools.
They are not necessarily intended as end user tools but as components aimed at developers.
Documentation and UX is aimed at developers not end users.

Brown Bear هو مشروع مفتوح المصدر يسترشد بمبادئ قوية ، ويهدف إلى أن يكون معياريًا ومرنًا وبدون رأي قوي للغاية بشأن تجربة المستخدم.
إنه مفتوح للمجتمع للمساعدة في تحديد اتجاهه.

- نمطي: يتضمن المشروع الكثير من المكونات التي لها وظائف محددة جيدًا وواجهات برمجة التطبيقات التي تعمل معًا.
- بطاريات متضمنة ولكنها قابلة للتبديل: يشتمل Brown Bear على مكونات كافية لبناء نظام حاويات كامل الميزات ، ولكن هيكله المعياري يضمن إمكانية تبديل معظم المكونات بتطبيقات مختلفة.
- الأمان القابل للاستخدام: يوفر Brown Bear إعدادات افتراضية آمنة دون المساس بإمكانية الاستخدام.
- التركيز على المطور: تهدف واجهات برمجة التطبيقات إلى أن تكون وظيفية ومفيدة لبناء أدوات قوية.
لا يُقصد بها بالضرورة أن تكون أدوات للمستخدم النهائي ولكن كمكونات تستهدف المطورين.
يهدف التوثيق وتجربة المستخدم إلى المطورين وليس المستخدمين النهائيين.

## Integrated Components

- Application Lifecycle Managgement (ALM) : Tuleap Community Edition inside Docker (Libre alternative open source project to Jira)
- Source Code Management (SCM) : Gitlab Community Edition; world first class Git management and CI agent runners for full automation, inside docker container
- Static Code Review : SonarQube community edition inside docker container 
- Code coverage : Gerrit an open source project for full static code enhancement 
- Image & Package Registry : SonaType Nexus community edition is used for image repo registry as integrated with Git automation
- Automation CI & Testing : Jenkins inside docker container is used here with all neccessary integration with other API's through tokens and rights as well as needed plugins to integrate SonarQube with Gerrit here : https://github.com/jenkinsci/sonar-gerrit-plugin
- Caching and persistence : Redis, MySQL, MariaDB and PostgreSQL are used to obtain best persistence of data for the whole stack as well as caching for better performance
- Monitoring : Prometheus is used to monitor Tuleap lifecycle
- Certificates and SSL : OpenLDAP and SSL are used to provide neccessary certificates, authhentication inside containers, and PHPOpenLDAP also used as UI for managing LDAP records



![Architecture](media/Screenshot_3.png "The Brown Bear Project Architecture")

# What's Gerrit

[Gerrit](https://www.gerritcodereview.com) is a code review and project management tool for Git based projects. Gerrit makes reviews easier by showing changes in a side-by-side display, and allowing inline comments to be added by any reviewer. Gerrit simplifies Git based project maintainership by permitting any authorized user to submit changes to the master Git repository, rather than requiring all approved changes to be merged in by hand by the project maintainer.

For only explicit docker integration with GitLab via OAuth Token
``` 
docker run -d --name gerrit-of -p 8080:8080 -p 29418:29418 \
-e AUTH_TYPE=DEVELOPMENT_BECOME_ANY_ACCOUNT \
-e WEBURL=http://my.public.url:8080 \
-e SMTP_SERVER=my.smtp.server \
-e USER_NAME="Gerrit CodeReview" \
-e USER_EMAIL=gerrit@my-provider.com \
-v ~/gerrit_volume:/var/gerrit/review_site \
-e GERRIT_INIT_ARGS="--install-plugin=analytics" \
-e GERRIT_INIT_ARGS="--install-plugin=replication" \
-e REPLICATION_REMOTES=gitlab \
-e GITLAB_REMOTE=https://oauth2:ACCESS_TOKEN@my.gitlab.server:MY_GROUP/\${name}.git \
-e GITLAB_REPLICATE_ON_STARTUP=true \
docker.io/openfrontier/gerrit
```
In this link https://github.com/cloudbees/gerrit-workflow-demo
You will find demo of Dockerized gerrit/jenkins workflow functionality

To demonstrate how Jenkins workflow facilitates complex build/testing schemes. Show how one can construct a Dockerized code review/automation environment with full integration
Demonstrate an integrated, containerized setup of Jenkins + Gerrit
This comprises 3 parts:

Jenkins server using workflow and the gerrit trigger plugin to work with gerrit patchsets
Gerrit server: acts as a central git repository and provides code review
Installation of git-repo tool (in both Jenkins and Gerrit), to support projects spanning multiple repositories
Code repos in gerrit for repo (umbrella manifest) and two sample Java projects


**Jenkins Plugin for Gerrit** This plugin triggers builds on events from the Gerrit code review system by retrieving events from the Gerrit command "stream-events", so the trigger is pushed from Gerrit instead of pulled as scm-triggers usually are.

Various types of events can trigger a build, multiple builds can be triggered by one event, and one consolidated report is sent back to Gerrit.

Multiple Gerrit server connections can be established per Jenkins instance. Each job can be configured with one Gerrit server.

https://github.com/jenkinsci/gerrit-trigger-plugin


# What's SonarQube
SonarQube is a Continuous Inspection, it provides the capability to not only show health of an application but also to highlight issues newly introduced. With a Quality Gate in place, you can [Clean As You Code](https://blog.sonarsource.com/clean-as-you-code) and therefore improve code quality systematically.
https://github.com/SonarSource/sonarqube

**Jenkins Plugin for SonarQube**
This plugin allow easy integration of [SonarQube™](https://www.sonarqube.org/), the open source platform for Continuous Inspection of code quality.
https://github.com/jenkinsci/sonarqube-plugin 


# What's Sonatype Nexus
Nexus is a repository manager. It allows you to proxy, collect, and manage your dependencies so that you are not constantly juggling a collection of JARs. It makes it easy to distribute your software. Internally, you configure your build to publish artifacts to Nexus and they then become available to other developers, support for the build types; Bower, Docker, Git LFS,  Maven, npm, NuGet, PyPI, Ruby Gems, Yum, APT, Conan, R, CPAN*, Raw (Universal), p2, Helm, ELPA*, Go, CocoaPods.

https://github.com/sonatype/nexus-public


**Jenkins Plugin for Sonatype Nexus**
Here in this link https://www.jenkins.io/doc/pipeline/steps/nexus-jenkins-plugin/, you will find how to use Jenkins Plugin for Nexus https://plugins.jenkins.io/nexus-jenkins-plugin/


# What's MailHog

MailHog is an email testing tool for developers:

* Configure your application to use MailHog for SMTP delivery
* View messages in the web UI, or retrieve them with the JSON API
* Optionally release messages to real SMTP servers for delivery

MailHog is an email testing tool for developers:

* Configure your application to use MailHog for SMTP delivery
* View messages in the web UI, or retrieve them with the JSON API
* Optionally release messages to real SMTP servers for delivery

Inspired by [MailCatcher](http://mailcatcher.me/), easier to install. Built with Go - MailHog runs without installation on multiple platforms.

https://github.com/mailhog/MailHog

# What's Jenkins
In a nutshell, Jenkins is the leading open-source automation server. 
Built with Java, it provides over 1,700 [plugins](https://plugins.jenkins.io/) to support automating virtually anything, 
so that humans can spend their time doing things machines cannot.

**What to Use Jenkins for and When to Use It**

Use Jenkins to automate your development workflow, so you can focus on work that matters most. Jenkins is commonly used for:

- Building projects
- Running tests to detect bugs and other issues as soon as they are introduced
- Static code analysis
- Deployment

Execute repetitive tasks, save time, and optimize your development process with Jenkins.

https://github.com/jenkinsci/jenkins

# What's Prometheus
**Prometheus**, a Cloud Native Computing Foundation project, is a systems and service monitoring system. It collects metrics from configured targets at given intervals, evaluates rule expressions, displays the results, and can trigger alerts when specified conditions are observed.

The features that distinguish Prometheus from other metrics and monitoring systems are:

A multi-dimensional data model (time series defined by metric name and set of key/value dimensions)
PromQL, a powerful and flexible query language to leverage this dimensionality
No dependency on distributed storage; single server nodes are autonomous
An HTTP pull model for time series collection
Pushing time series is supported via an intermediary gateway for batch jobs
Targets are discovered via service discovery or static configuration
Multiple modes of graphing and dashboarding support
Support for hierarchical and horizontal federation
https://github.com/prometheus/prometheus


# What's Tuleap?

**Tuleap Open ALM** is a Libre and Open Source tool for end to end traceability of application and system developments.

Development occurs in realtime on [https://tuleap.net](https://tuleap.net).
Sources are available in a [Git repository on Tuleap.net](https://tuleap.net/plugins/git/tuleap/tuleap/stable).
You can find a mirror on [GitHub](https://github.com/Enalean/tuleap).

Use case, screen shots & more on [https://tuleap.org](https://www.tuleap.org/what-is-tuleap).


# How to use it?


You should first [install it](https://www.tuleap.org/get-started), it can be either with [RHEL7 packages](https://docs.tuleap.org/installation-guide/full-installation.html)
or [docker image](https://docs.tuleap.org/installation-guide/docker-image.html) and then you can find on tuleap.org a
bunch of [documentation](https://docs.tuleap.org/) and [tutorials](https://www.tuleap.org/resources/videos-tutorials).


# The Brown Bear Project Git Workflow

![Architecture](media/Screenshot_2.png "The Brown Bear Project Architecture")

# Note:
All containers are running explicitly in one context, so that is why no ports needed to be exported. You may find three docker-compose.yml manifest files, for full testing, Mac and overall docker-compose.yml, which is the core file to spwn up using:

```
docker compose -f docker-compose.yml up -d
```

Fix permissins with: 
```
chown -R 200:200 ${NEXUS_VOLUME_DATA} chown -R 1000:1000 ${JENKINS_VOLUME_HOME}
```



You will better use Linux of any stable flavor VM to spwan this node with all components oor you may also extract explicit docker's from the main docker-compose for diifferent deployments on separate machines/VM's, in this case be aware that all are reachable within same network. 

You will find an under testing Traefik implementation for this stack in docker-compose-all-traefil.yml where Traefik may replace Nginx and do end-to-end container networking, TLS termination, domains, SSL (with use of external provider or internally using LDAP) and as well full networking between containers in stack. Currently this stack is tested for Nginx reverse proxy and TLS.

All latest image releases are in Docker Hub for your reference

**.env-sample**
These are mandatory environment variables used in the deployment, a sample provided .env-sample, you just need to tweek it per your own values and rename it to .env before deployment

Legal
=====

*Brought to you courtesy of our legal counsel. For more context,
please see the [NOTICE](https://github.com/yasir2000/brown-bear/blob/master/NOTICE) document in this repo.*

Use and transfer of Brown Bear may be subject to certain restrictions by the
United States and other governments.

It is your responsibility to ensure that your use and/or transfer does not
violate applicable laws.

For more information, please see https://www.bis.doc.gov

Licensing
=========
Brown Bear is licensed under the Apache License, Version 2.0. See
[LICENSE](https://github.com/yasir2000/brown-bear/blob/master/LICENSE) for the full
license text.

