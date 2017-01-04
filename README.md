# sync
Moodle plugin

--------------------------------------------------
Synchronizations plugin with Omega for Moodle 3.0+
Version: 1.0.0
--------------------------------------------------

Authors:
* Hans Jeria (hansjeria@gmail.com)
* Mark Michaelsen (mmichaelsen678@gmail.com) 
* Joaquín Rivano (jrivano@alumnos.uai.cl)
* Javier González (javiergonzalez@alumnos.uai.cl)

Release notes
-------------

1.0.0: First official deploy

NOTE
----

* This plugin was developed with Moodle 3.0.6 and it is not guaranteed it's compatibility with Moodle 3.1+ or further. Once tested, this will be updated.
* Some functions require PHP 7+. Please make sure you are running a compatible version.
* Omega is a parallel platform used in Adolfo Ibáñez University (UAI). If you want to use this plugin for another service, we recommend checking out the settings.php & classes pages to edit and adapt the functions for your own services. Further changes may be required for other custom synchronizations.

Introduction
------------

This project brings an easy way to synchronize the Moodle platform with Omega's services, such as course creation and user enrolment. The creation menu displays all active academic periods including useful information in order to make the process as simple as possible, and the records and history tables save every synchronization request and data obtained, letting administrators track and manage them without much effort.

The plugin schedules tasks (https://docs.moodle.org/dev/Task_API) running in the platform's CRON every day out of active hours, as it takes a while to retrieve all the information. This data is then synchronized with the actual courses and user enrolments in the platform, keeping it constantly updated.

Installation
------------

In order to install Sync, this project must be placed in the /local/ directory of the platform and named 'sync'. Then, whenever the main site is run, it should prompt the plugin's installation, along with the required Omega's services settings (URLs & token). If it's not the case, these settings can be set at the plugins options in the site's administration page.

Acknowledgments, suggestions, complaints and bug reporting
----------------------------------------------------------

If you have any sugestions or feedback about this project, we would be happy and grateful to hear about it. You may contact us via email to any of the authors mentioned above.