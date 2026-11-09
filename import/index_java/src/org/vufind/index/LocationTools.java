
package org.vufind.index;

import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import java.util.Set;
import java.util.LinkedHashSet;
import java.util.List;

public class LocationTools {

    public Set<String> getBuildings(Record record) {
        Set<String> result = new LinkedHashSet<>();

        // Pull fields as standard generic VariableField elements
        List<VariableField> fields = record.getVariableFields("596");

        for (VariableField vf : fields) {
            // Confirm the field is a DataField structure before casting
            if (vf instanceof DataField) {
                DataField field = (DataField) vf;
                List<Subfield> subfields = field.getSubfields('a');

                for (Subfield subfield : subfields) {
                    String data = subfield.getData();
                    if (data != null) {
                        // Split the string on any whitespace barrier
                        String[] tokens = data.trim().split("\\s+");
                        for (String token : tokens) {
                            if (!token.isEmpty()) {
                                result.add(token);
                            }
                        }
                    }
                }
            }
        }
        return result.isEmpty() ? null : result;
    }
}
