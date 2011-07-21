ASPIRE_DIR := /Users/alandix/Documents/Talis/data/aspire/
META_DIR   := /Users/alandix/Documents/Talis/data/aspire/20110401040135/metabox
P_DIR      := /Users/alandix/Documents/Talis/data/aspire/processed

URI_BASE_ID     := 10000000      # same length means sort order = numeric order
LITERAL_BASE_ID := 20000000
CLUSTER_BASE_ID := 30000000

#OLD stuff 
$(P_DIR)/subjects.txt: $(META_DIR)/meta
	time cut -f1 -d' ' <$(META_DIR)/meta >$(P_DIR)/subjects.txt

$(P_DIR)/predicates.txt: $(META_DIR)/meta
	time cut -f2 -d' ' <$(META_DIR)/meta >$(P_DIR)/predicates.txt

$(P_DIR)/subjects-unique.txt: $(P_DIR)/subjects.txt
	time sort -u <$(P_DIR)/subjects.txt >$(P_DIR)/subjects-unique.txt

$(P_DIR)/predicates-unique.txt: $(P_DIR)/predicates.txt
	time sort -u <$(P_DIR)/predicates.txt >$(P_DIR)/predicates-unique.txt

$(P_DIR)/all-unique.txt: $(P_DIR)/subjects-unique.txt $(P_DIR)/predicates-unique.txt
	cat $(P_DIR)/subjects-unique.txt $(P_DIR)/predicates-unique.txt | time sort -u >$(P_DIR)/all-unique.txt

# proper stuff 

$(P_DIR)/uri-subjects.txt $(P_DIR)/uri-predicates.txt $(P_DIR)/uri-objects.txt $(P_DIR)/literal-objects.txt: $(META_DIR)/meta
	time php extract-objects-2.php

$(P_DIR)/uri-subjects-unique.txt: $(P_DIR)/uri-subjects.txt
	time sort -u <$(P_DIR)/uri-subjects.txt >$(P_DIR)/uri-subjects-unique.txt

$(P_DIR)/uri-predicates-unique.txt: $(P_DIR)/uri-predicates.txt
	time sort -u <$(P_DIR)/uri-predicates.txt >$(P_DIR)/uri-predicates-unique.txt

$(P_DIR)/uri-objects-unique.txt: $(P_DIR)/uri-objects.txt
	time sort -u <$(P_DIR)/uri-objects.txt >$(P_DIR)/uri-objects-unique.txt

$(P_DIR)/uri-entities-unique.txt: $(P_DIR)/uri-subjects-unique.txt $(P_DIR)/uri-objects-unique.txt
	cat $(P_DIR)/uri-subjects-unique.txt $(P_DIR)/uri-objects-unique.txt | time sort -u >$(P_DIR)/uri-entities-unique.txt

$(P_DIR)/uri-all-unique.txt: $(P_DIR)/uri-predicates-unique.txt  $(P_DIR)/uri-entities-unique.txt
	cat $(P_DIR)/uri-predicates-unique.txt  $(P_DIR)/uri-entities-unique.txt | time sort -u >$(P_DIR)/uri-all-unique.txt

$(P_DIR)/literal-all-unique.txt: $(P_DIR)/literal-objects.txt
	time sort -u <$(P_DIR)/literal-objects.txt >$(P_DIR)/literal-all-unique.txt

$(P_DIR)/uri-ids.txt $(P_DIR)/literal-ids.txt: $(P_DIR)/uri-all-unique.txt $(P_DIR)/literal-all-unique.txt
	php --php-ini php.ini create-ids.php

$(P_DIR)/uri-ids-sorted.txt: $(P_DIR)/uri-ids.txt
	time sort $(P_DIR)/uri-ids.txt >$(P_DIR)/uri-ids-sorted.txt  # numeric order not sort order

$(P_DIR)/spo.txt $(P_DIR)/sop.txt $(P_DIR)/ops.txt $(P_DIR)/pso.txt: $(META_DIR)/meta $(P_DIR)/uri-ids.txt $(P_DIR)/literal-ids.txt
	time php --php-ini php.ini triples-to-ids.php

$(patsubst %,$(P_DIR)/%-sorted.txt,spo sop ops pso): $(P_DIR)/%-sorted.txt: $(P_DIR)/%.txt
	time sort -u <$< >$@

$(P_DIR)/sp-sorted.txt: $(P_DIR)/spo-sorted.txt
	cut -f1,2 $(P_DIR)/spo-sorted.txt | time sort -u > $(P_DIR)/sp-sorted.txt

$(P_DIR)/op-sorted.txt: $(P_DIR)/ops-sorted.txt
	cut -f1,2 $(P_DIR)/ops-sorted.txt | time sort -u > $(P_DIR)/op-sorted.txt

$(P_DIR)/sp-dir.txt: $(P_DIR)/sp-sorted.txt
	time lam $(P_DIR)/sp-sorted.txt -s$$'\011'+  >$(P_DIR)/sp-dir.txt

$(P_DIR)/op-dir.txt: $(P_DIR)/op-sorted.txt
	time lam $(P_DIR)/op-sorted.txt -s$$'\011'-  >$(P_DIR)/op-dir.txt

$(P_DIR)/ep-dir.txt: $(P_DIR)/sp-dir.txt $(P_DIR)/op-dir.txt
	time sort -u  $(P_DIR)/sp-dir.txt $(P_DIR)/op-dir.txt > $(P_DIR)/ep-dir.txt

$(P_DIR)/p-ct.txt $(P_DIR)/ps-ct.txt $(P_DIR)/po-ct.txt: $(P_DIR)/pso-sorted.txt
	time php --php-ini php.ini pred-counts.php

$(P_DIR)/p-ids.txt: $(P_DIR)/uri-predicates-unique.txt $(P_DIR)/uri-ids.txt
	time php --php-ini php.ini pred-ids.php

$(P_DIR)/rdf-type-id.txt: $(P_DIR)/p-ids.txt
	fgrep 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' $(P_DIR)/p-ids.txt | cut -f1 >$(P_DIR)/rdf-type-id.txt
	# e.g. 1764407    

$(P_DIR)/types.txt: $(P_DIR)/rdf-type-id.txt $(P_DIR)/spo-sorted.txt
	time fgrep $$'\011'`cat $(P_DIR)/rdf-type-id.txt`$$'\011' <$(P_DIR)/spo-sorted.txt | cut -f1,3 >$(P_DIR)/types.txt 

$(P_DIR)/type-ids.txt: $(P_DIR)/types.txt $(P_DIR)/uri-ids-sorted.txt
	cut -f2 <$(P_DIR)/types.txt | time sort -u | time join -t $$'\011' -1 1 -2 1 - $(P_DIR)/uri-ids-sorted.txt >$(P_DIR)/type-ids.txt

$(P_DIR)/types-code.txt: $(P_DIR)/types.txt
	time lam $(P_DIR)/types.txt -s$$'\011't  >$(P_DIR)/types-code.txt

$(P_DIR)/pp-ct.txt: $(P_DIR)/p-ids.txt $(P_DIR)/ep-dir.txt
	time php --php-ini php.ini pred-pred-counts.php

$(P_DIR)/simple-counts.txt: $(P_DIR)/spo.txt $(P_DIR)/uri-entities-unique.txt $(P_DIR)/uri-subjects-unique.txt $(P_DIR)/uri-objects-unique.txt $(P_DIR)/uri-predicates-unique.txt $(P_DIR)/literal-all-unique.txt $(P_DIR)/type-ids.txt
	cat /dev/null >$(P_DIR)/simple-counts.txt
	echo "triples:" `wc -l <$(P_DIR)/spo.txt` >>$(P_DIR)/simple-counts.txt
	echo "entities:" `wc -l <$(P_DIR)/uri-entities-unique.txt` >>$(P_DIR)/simple-counts.txt
	echo "subjects:" `wc -l <$(P_DIR)/uri-subjects-unique.txt` >>$(P_DIR)/simple-counts.txt
	echo "objects:" `wc -l <$(P_DIR)/uri-objects-unique.txt` >>$(P_DIR)/simple-counts.txt
	echo "predicates:" `wc -l <$(P_DIR)/uri-predicates-unique.txt` >>$(P_DIR)/simple-counts.txt
	echo "literals:" `wc -l <$(P_DIR)/literal-all-unique.txt` >>$(P_DIR)/simple-counts.txt
	echo "types:" `wc -l <$(P_DIR)/type-ids.txt` >>$(P_DIR)/simple-counts.txt

$(P_DIR)/clusters.txt: $(P_DIR)/p-ids.txt $(P_DIR)/p-ct.txt $(P_DIR)/pp-ct.txt $(P_DIR)/simple-counts.txt
	time php --php-ini php.ini calc-stats.php  # version to create cluster file

$(P_DIR)/ec2-code.txt: $(P_DIR)/p-ids.txt $(P_DIR)/ep-dir.txt $(P_DIR)/clusters.txt
	time php --php-ini php.ini classify-on-threshold.php 2 c $(P_DIR)/ec2-code.txt

$(P_DIR)/ec1-code.txt: $(P_DIR)/p-ids.txt $(P_DIR)/ep-dir.txt $(P_DIR)/clusters.txt
	time php --php-ini php.ini classify-on-threshold.php 1 1 $(P_DIR)/ec1-code.txt

$(P_DIR)/ect-code.txt: $(P_DIR)/types-code.txt $(P_DIR)/ec2-code.txt
	#time sort -u  $(P_DIR)/types-code.txt $(P_DIR)/ec2-code.txt > $(P_DIR)/ect-code.txt
	time sort -u  $(P_DIR)/types-code.txt $(P_DIR)/ec2-code.txt $(P_DIR)/ep-dir.txt > $(P_DIR)/ect-code.txt

$(P_DIR)/class-type-ct.txt: $(P_DIR)/p-ids.txt $(P_DIR)/ect-code.txt
	time php --php-ini php.ini class-type-counts.php

relations: $(P_DIR)/type-ids.txt $(P_DIR)/class-type-ct.txt
	# not created just run for output
	time php --php-ini php.ini calc-relations.php


