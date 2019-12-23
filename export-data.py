#!/usr/bin/python3

#  Copyright (C) 2019 Sam Steele
#  Licensed under the Apache License, Version 2.0 (the "License");
#  you may not use this file except in compliance with the License.
#  You may obtain a copy of the License at
#
#  http://www.apache.org/licenses/LICENSE-2.0
#
#  Unless required by applicable law or agreed to in writing, software
#  distributed under the License is distributed on an "AS IS" BASIS,
#  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#  See the License for the specific language governing permissions and
#  limitations under the License.

import sys, json
from datetime import datetime, date, timedelta
from influxdb import InfluxDBClient
from influxdb.exceptions import InfluxDBClientError

INFLUXDB_HOST = 'localhost'
INFLUXDB_PORT = 8086
INFLUXDB_USERNAME = 'root'
INFLUXDB_PASSWORD = 'root'

def insert_data(report, measurement, timeFilter):
	report[measurement] = list(client.query("SELECT * FROM %s WHERE %s" % (measurement, timeFilter)).get_points())

def write_report(month, year):
	print("Writing report to %s-%s.json" % (year, month))
	f = open("%s-%s.json" % (year, month), "w")
	f.write(json.dumps(generate_report(month, year)))
	f.close()

def generate_report(month, year):
	report = {}
	startTime = datetime(year, month, 1).isoformat() + 'Z'
	if month < 12:
		endTime = datetime(year, month + 1, 1).isoformat() + 'Z'
	else:
		endTime = datetime(year + 1, 1, 1).isoformat() + 'Z'
	
	timeFilter = "time >= '%s' AND time < '%s'" % (startTime, endTime)

	client.switch_database('fitbit')
	report['fitbit'] = {}
	insert_data(report['fitbit'], 'distance', timeFilter)
	insert_data(report['fitbit'], 'steps', timeFilter)
	insert_data(report['fitbit'], 'floors', timeFilter)
	insert_data(report['fitbit'], 'restingHeartRate', timeFilter)
	insert_data(report['fitbit'], 'weight', timeFilter)
	insert_data(report['fitbit'], 'steps', timeFilter)
	insert_data(report['fitbit'], 'sleep', timeFilter)
	insert_data(report['fitbit'], 'minutesLightlyActive', timeFilter)
	insert_data(report['fitbit'], 'minutesFairlyActive', timeFilter)
	insert_data(report['fitbit'], 'minutesVeryActive', timeFilter)
	insert_data(report['fitbit'], 'minutesSedentary', timeFilter)

	client.switch_database('instagram')
	report['instagram'] = {}
	insert_data(report['instagram'], 'post', timeFilter)
	insert_data(report['instagram'], 'followers', timeFilter)

	client.switch_database('foursquare')
	report['foursquare'] = {}
	insert_data(report['foursquare'], 'checkin', timeFilter 
		+ " AND category != 'Assisted Living'"
		+ " AND category != 'Home (private)'"
		+ " AND category != 'Housing Development'"
		+ " AND category != 'Residential Building (Apartment / Condo)'"
		+ " AND category != 'Trailer Park'")

	client.switch_database('gaming')
	report['gaming'] = {}
	insert_data(report['gaming'], 'time', timeFilter + " AND platform != 'Google Play'")
	insert_data(report['gaming'], 'achievement', timeFilter + " AND platform != 'Google Play'")

	client.switch_database('rescuetime')
	report['rescuetime'] = list(client.query("SELECT \"category\",\"duration\",\"productivity\" FROM %s WHERE %s" % ('activity', timeFilter)).get_points())

	return report

try:
    client = InfluxDBClient(host=INFLUXDB_HOST, port=INFLUXDB_PORT, username=INFLUXDB_USERNAME, password=INFLUXDB_PASSWORD)
except InfluxDBClientError as err:
    print("InfluxDB connection failed: %s" % (err))
    sys.exit()

print(json.dumps(generate_report(date.today().month, date.today().year)))
