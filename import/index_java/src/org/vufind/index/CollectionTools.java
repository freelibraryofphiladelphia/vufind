
package org.vufind.index;

import org.marc4j.marc.Record;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import java.util.Set;
import java.util.LinkedHashSet;

public class CollectionTools {

    public Set<String> getCollection(Record record) {
        Set<String> result = new LinkedHashSet<>();

        String leader = record.getLeader().toString();
        char leaderBit06 = (leader.length() > 6) ? Character.toUpperCase(leader.charAt(6)) : ' ';

        // 1. Evaluate Fleisher Collection Conditions (Leader code C or D + 010$o containing "FLC")
        if (leaderBit06 == 'C' || leaderBit06 == 'D') {
            DataField f010 = (DataField) record.getVariableField("010");
            if (f010 != null) {
                Subfield subO = f010.getSubfield('o');
                if (subO != null && subO.getData().toUpperCase().contains("FLC")) {
                    result.add("FleisherCollection");
                }
            }
        }

        // 2. Evaluate Rare Book Conditions (852$b containing "RBD")
        DataField f852 = (DataField) record.getVariableField("852");
        if (f852 != null) {
            Subfield subB = f852.getSubfield('b');
            if (subB != null && subB.getData().toUpperCase().contains("RBD")) {
                result.add("RareBookCollection");
            }
        }

        return result.isEmpty() ? null : result;
    }
}
