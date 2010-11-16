#!/usr/bin/env python2.5

# Copyright 2009-2010 bjweeks, MZMcBride, svick

# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.

import datetime
import MySQLdb
import wikitools
import settings

report_title_main = settings.rootpage + 'WikiProjects by cleanup'

report_template = u'''
List of WikiProjects with cleanup articles; \
data as of <onlyinclude>%s</onlyinclude>.
{| class="wikitable sortable plainlinks"
|-
! WikiProject
! By Article
! By Category
! Statistics
! Date
|-
%s
|}
'''

#Login to wiki
wiki = wikitools.Wiki(settings.apiurl)
wiki.setMaxlag(-1)
wiki.login(settings.username, settings.password)

#Login to db
try:
    conn = MySQLdb.connect(    host=settings.host,
                            db=settings.dbname,
                            read_default_file='~/.my.cnf')
except MySQLdb.Error, e:
    print "MYSQL Login Error: %d: %s" % (e.args[0], e.args[1])
    sys.exit (1)

cursor = conn.cursor()

#Fetch list of projects
cursor.execute(u'''
    /* project_changes.py */
    SELECT name
    FROM projects
    WHERE active = 1
    AND last_run_id IS NOT NULL
    ORDER BY name
    ''')

projects = []
for row in cursor.fetchall():
    projects.append(unicode(row[0], 'utf-8'))


#Get the projects' cleanup
for project in projects:

    #First by article
    #Get project + run id
    cursor.execute(u'''
        SELECT
            projects.id AS id,
            projects.is_wikiproject AS is_wikiproject,
            runs.id AS run_id,
            runs.time AS time
        FROM projects
        JOIN runs ON projects.last_run_id = runs.id
        WHERE name = '%s'
        ''' % project)

    projectsqldata = cursor.fetchone()

    project_name = project
    project_name_human = str_replace('_', ' ', project_name)
    project_id = projectsqldata['id']
    run_id = projectsqldata['run_id']
    run_time = projectsqldata['time']
    is_wikiproject = projectsqldata['is_wikiproject']
    if is_wikiproject == 1:
        project_name = "WikiProject_%s" % project_name
        project_name_human = "WikiProject %s" % project_name_human

    #Load articles
    cursor.execute(u'''
        SELECT
            id,
            article,
            importance,
            class,
            (SELECT COUNT(*) FROM categories WHERE articles.id = categories.article_id) AS count
        FROM articles
        WHERE run_id = run_id
        AND project_id = project_id
        ORDER BY article
        ''' % (run_id, project_id))
    articles = cursor.fetchall()

    byarticleoutput = []
    byarticleoutput.append(u'''
List of articles tagged for cleanup by article in %s; \
data as of <onlyinclude>%s</onlyinclude>.

{| class="wikitable sortable plainlinks" style="width:100%%; margin:auto;"
|- style="white-space:nowrap;"
! Article
! Importance
! Class
! Count
! Categories
|-
''' % (project_name, run_time)
    byarticleoutputlength = 0
    byarticleoutputcounter = 1
    for article in articles:
        cursor.execute(u'''
            SELECT
                name,
                month,
                year
            FROM categories
            WHERE article_id = %s
            ''' % article['id'])

        #Load categories
        categories = ""
        categoriescount = 0
        months = ["","January", "February", "March", "April", "June", "July", "August", "September", "October", "November", "December"]
        category_rows = cursor.fetchall()
        for category in category_rows:
            categories += "%s (%s %s), " % (category['name'], months[category['month']], category['year'])

        #remove last comma + space
        categories = categories[:-2]

        if byarticleoutputlength > 150000:
            byarticleoutput.append("\n|}")
            report = wikitools.Page(wiki, report_title + "/" + project + + "(a) (" + byarticleoutputcounter + ")")
            report_text = str.join(byarticleoutput)
            report_text = report_text.encode('utf-8')
            report.edit(report_text, summary=settings.editsumm, bot=1)
            byarticleoutput = []
            byarticleoutputlength = 0
            byarticleoutputcounter += 1

        byarticleoutput.append(u"|[[%s]]\n|%s\n|%s\n|%s\n|%s\n|-\n" % (article['article'], article['importance'],article['class'],categoriescount,categories))
        byarticleoutputlength += byarticleoutputlength[-1]

    #finish print out by article
    byarticleoutput.append("\n|}")
    if byarticleoutputcounter > 1:
        report = wikitools.Page(wiki, report_title + "/" + project + "(a) (" + byarticleoutputcounter + ")"
    else:
        report = wikitools.Page(wiki, report_title + "/" + project + "(a)")
            report_text = str.join(byarticleoutput)
    report_text = str.join(byarticleoutput)
    report_text = report_text.encode('utf-8')
    report.edit(report_text, summary=settings.editsumm, bot=1)
    byarticleoutput = []
    byarticleoutputlength = 0
    byarticleoutputcounter = 0
    articles = []

    #######
    #By category
    cursor.execute(u'''SELECT categories.name AS name, COUNT(*) AS count
FROM categories
JOIN articles on categories.article_id = articles.id
WHERE articles.run_id = %s
AND articles.project_id = %s
GROUP BY categories.name
''' % (run_id,project_id))


    bycategoryoutput = []
    bycategoryoutput.append(u'''
List of articles tagged for cleanup by category in %s; \
data as of <onlyinclude>%s</onlyinclude>.

''' % (project_name, run_time)
    bycategoryoutputlength = 0
    bycategoryoutputcounter = 1
    sections_query = cursor.fetchall()
    for section in sections_query:
        byarticleoutput.append(u'''==%s (%s)=={| class="wikitable sortable plainlinks" style="width:100%%; margin:auto;"
|- style="white-space:nowrap;"
! Article
! Importance
! Class
! Month
! Count
! Categories
|-
''' % (section['name'],section['count'])
        cursor.execute(u'''
SELECT DISTINCT
id,
article,
importance,
class,
(
    SELECT COALESCE(CONCAT(MONTHNAME(CONCAT(year, '-', month, '-01')), ' ', year), year)
    FROM categories AS c
    WHERE c.article_id = articles.id
    AND c.name = '%s'
    ORDER BY year, month
    LIMIT 1
) AS month
FROM articles
JOIN categories ON articles.id = categories.article_id
WHERE run_id = %s
AND project_id = %s
AND categories.name = '{$section['name']}'
ORDER BY article''' % (section['name'],run_id,project_id,section['name'])
        articles = cursor.fetchall()

        for article in articles:
            cursor.execute(u'''
                SELECT
                    name,
                    month,
                    year
                FROM categories
                WHERE article_id = %s
                ''' % article['id'])

            #Load categories (by cat)
            categories = ""
            categoriescount = 0
            months = ["","January", "February", "March", "April", "June", "July", "August", "September", "October", "November", "December"]
            category_rows = cursor.fetchall()
            for category in category_rows:
                categories += "%s (%s %s), " % (category['name'], months[category['month']], category['year'])

            #remove last comma + space (by cat)
            categories = categories[:-2]

            if bycategoryoutputlength > 150000:
                bycategoryoutput.append("\n|}")
                report = wikitools.Page(wiki, report_title + "/" + project + + "(c) (" + bycategoryoutputcounter + ")")
                report_text = str.join(bycategoryoutput)
                report_text = report_text.encode('utf-8')
                report.edit(report_text, summary=settings.editsumm, bot=1)
                bycategoryoutput = []
                bycategoryoutputlength = 0
                bycategoryoutputcounter += 1

            bycategoryoutput.append(u"|[[%s]]\n|%s\n|%s\n|%s\n|%s\n|-\n" % (article['article'], article['importance'],article['class'],categoriescount,categories))
            bycategoryoutputlength += bycategoryoutputlength[-1]

        #finish print out by article
        bycategoryoutput.append("\n|}")
        if bycategoryoutputcounter > 1:
            report = wikitools.Page(wiki, report_title + "/" + project + "(a) (" + bycategoryoutputlength + ")"
        else:
            report = wikitools.Page(wiki, report_title + "/" + project + "(a)")
                report_text = str.join(bycategoryoutput)
        report_text = str.join(bycategoryoutput)
        report_text = report_text.encode('utf-8')
        report.edit(report_text, summary=settings.editsumm, bot=1)
        bycategoryoutput = []
        bycategoryoutputlength = 0
        articles = []

 #close
cursor.close()
conn.close()

#UNTESTED CODE! Written 11-15-2010 smallman
#based on:
# https://github.com/svick/Wikipedia-cleanup-listing/blob/master/pub/CleanupListingByCat.php
# https://github.com/svick/Wikipedia-cleanup-listing/blob/master/pub/CleanupListing.php
# https://github.com/mzmcbride/database-reports/blob/master/project_changes.py