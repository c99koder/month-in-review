# month-in-review

WordPress plugin to automatically generate monthly reports using data exported from InfluxDB.
See https://github.com/c99koder/personal-influxdb for a collection of scripts to import data into InfluxDB.

# Configuration and Usage

* Download [FontAwesome](https://fontawesome.com/how-to-use/on-the-web/setup/hosting-font-awesome-yourself) and extract it into your `wp-content` folder

* Export the current month's data from InfluxDB into a JSON file:
```
$ python3 ./export-data.py > `date +%Y-%-m`.json
```

* Edit `month-in-review.php` and set the path to the JSON files.  You can also set the category for the posts.

* Copy `month-in-review.php` and `style.css` into `wp-content/plugins/month-in-review`

* Activate the plugin in WordPress

![Screenshot](https://raw.githubusercontent.com/c99koder/month-in-review/master/screenshots/month-in-review.png)

# License

Copyright (C) 2019 Sam Steele. Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.