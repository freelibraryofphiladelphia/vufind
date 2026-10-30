package org.vufind.index;

import org.marc4j.marc.Record;
import org.solrmarc.index.SolrIndexer;

public class CatkeyTools
{
    public String prefixRemove(Record record, String fieldSpec)
    {
        String value = SolrIndexer.instance().getFirstFieldVal(record, fieldSpec);

        if (value == null || value.isEmpty()) {
            return value;
        }

        return value.startsWith("a")
            ? value.substring(1)
            : value;
    }
}
