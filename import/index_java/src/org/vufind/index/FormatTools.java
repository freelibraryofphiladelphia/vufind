
package org.vufind.index;

import org.marc4j.marc.Record;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;
import java.util.Set;
import java.util.LinkedHashSet;

public class FormatTools {

    public Set<String> getFormat(Record record) {
        Set<String> result = new LinkedHashSet<>();

        // Initialize 099 field values
        DataField localCallNumber = (DataField) record.getVariableField("099");
        String localCallNumberSubfieldA = null;
        if (localCallNumber != null) {
            Subfield subA = localCallNumber.getSubfield('a');
            if (subA != null) {
                localCallNumberSubfieldA = subA.getData().toLowerCase();
            }
        }

        // Initialize 245 (title) field values
        DataField title = (DataField) record.getVariableField("245");
        String titleSubfieldH = null;
        if (title != null) {
            Subfield subH = title.getSubfield('h');
            if (subH != null) {
                titleSubfieldH = subH.getData().toLowerCase();
            }
        }

        // Initialize 300 (physical description) field values
        DataField physicalDescription = (DataField) record.getVariableField("300");
        String physicalDescriptionSubfieldA = null;
        if (physicalDescription != null) {
            Subfield subA = physicalDescription.getSubfield('a');
            if (subA != null) {
                physicalDescriptionSubfieldA = subA.getData().toLowerCase();
            }
        }

        // Initialize Leader positions safely
        String leader = record.getLeader().toString();
        char leaderBit06 = (leader.length() > 6) ? Character.toUpperCase(leader.charAt(6)) : ' ';
        char leaderBit07 = (leader.length() > 7) ? Character.toUpperCase(leader.charAt(7)) : ' ';

        ControlField fixedField = (ControlField) record.getVariableField("008");

        switch (leaderBit06) {
            case 'A': // Language material (Book / Serials)
                if (leaderBit07 == 'A' || leaderBit07 == 'C' || leaderBit07 == 'D' || leaderBit07 == 'M') {
                    if (localCallNumberSubfieldA != null) {
                        if (localCallNumberSubfieldA.contains("overdrive")) {
                            result.add("BookOverDrive");
                            break;
                        } else if (!localCallNumberSubfieldA.isEmpty() && localCallNumberSubfieldA.charAt(0) == 'j') {
                            result.add("BookChildren");
                            break;
                        }
                    } else if (titleSubfieldH != null) {
                        if (titleSubfieldH.contains("[electronic resource]")) {
                            result.add("ElectronicResource");
                            break;
                        } else if (titleSubfieldH.contains("[microform]")) {
                            result.add("LanguageMaterialMicroform");
                            break;
                        }
                    }
                } else if (leaderBit07 == 'B' || leaderBit07 == 'I' || leaderBit07 == 'S') {

                    if (titleSubfieldH != null && titleSubfieldH.contains("[electronic resource]")) {
                        result.add("ElectronicResource");
                        break;
                    }

                    if (fixedField != null && fixedField.getData().length() > 21) {
                        char formatContResource = Character.toUpperCase(fixedField.getData().charAt(21));
                        switch (formatContResource) {
                            case 'D': result.add("UpdatingDatabase");
                                break;
                            case 'L': result.add("UpdatingLooseLeaf");
                                break;
                            case 'M': result.add("MonographicSeries");
                                break;
                            case 'N': result.add("Newspaper");
                                break;
                            case 'P': result.add("Periodical");
                                break;
                            case 'W': result.add("UpdatingWebsite");
                                break;
                            default: result.add("ContinuingResources");
                                break;
                        }
                    }
                }
                if (result.isEmpty()) {
                    result.add("LanguageMaterial");
                }
                break;

            case 'C': result.add("NotatedMusic"); break;
            case 'D': result.add("ManuscriptNotatedMusic"); break;
            case 'E': result.add("CartographicMaterial"); break;
            case 'F': result.add("ManuscriptCartographicMaterial"); break;

            case 'G': // Projected medium (Video, Film, DVD)
                if (localCallNumberSubfieldA != null) {
                    if (localCallNumberSubfieldA.contains("blu")) {
                        result.add("ProjectedBluRay");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("overdrive")) {
                        result.add("ProjectedOverDrive");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("dvd")) {
                        result.add("ProjectedVideoDisc");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("videocassette")) {
                        result.add("ProjectedCassette");
                        break;
                    }
                } else if (physicalDescriptionSubfieldA != null) {
                    if (physicalDescriptionSubfieldA.contains("cassette")) {
                        result.add("ProjectedCassette");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("videodisc")) {
                        result.add("ProjectedVideoDisc");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("videocassette")) {
                        result.add("ProjectedCassette");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("disc")) {
                        result.add("ProjectedVideoDisc");
                        break;
                    }
                } else if (titleSubfieldH != null && titleSubfieldH.contains("[electronic resource]")) {
                    result.add("ElectronicResource");
                    break;
                }
                result.add("ProjectedMedium");
                break;

            case 'I': // Nonmusical sound recording (Audiobooks / Podcasts)
                if (localCallNumberSubfieldA != null) {
                    if (localCallNumberSubfieldA.contains("overdrive")) {
                        result.add("NonMusicalOverDrive");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("disc")) {
                        result.add("NonMusicalDisc");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("cassette")) {
                        result.add("NonMusicalCassette");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("podcast")) {
                        result.add("NonMusicalMP3");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("downloadable audiobook")) {
                        result.add("NonMusicalOneClick");
                        break;
                    }
                } else if (physicalDescriptionSubfieldA != null) {
                    if (physicalDescriptionSubfieldA.contains("disc")) { result.add("NonMusicalDisc");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("cassette")) {
                        result.add("NonMusicalCassette");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("mp3")) {
                        result.add("NonMusicalMP3");
                        break;
                    }
                }
                result.add("NonmusicalSoundRecording");
                break;

            case 'J': // Musical sound recording
                if (localCallNumberSubfieldA != null) {
                    if (localCallNumberSubfieldA.contains("overdrive")) {
                        result.add("MusicalOverDrive");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("phonodisc") || localCallNumberSubfieldA.contains("vinyl")) {
                        result.add("MusicalPhonodisc");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("disc")) {
                        result.add("MusicalSoundDisc");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("cassette")) {
                        result.add("MusicalCassette");
                        break;
                    }
                } else if (physicalDescriptionSubfieldA != null) {
                    if (physicalDescriptionSubfieldA.contains("disc")) {
                        result.add("MusicalSoundDisc");
                        break;
                    }
                    else if (physicalDescriptionSubfieldA.contains("cassette")) {
                        result.add("MusicalCassette");
                        break;
                    }
                }
                result.add("MusicalSoundRecording");
                break;

            case 'K': result.add("TwoDimensionalNonProjectableGraphic"); break;

            case 'M': // Computer file
                if (localCallNumberSubfieldA != null) {
                    if (localCallNumberSubfieldA.contains("overdrive")) {
                        result.add("ComputerFileOverdrive");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("jstor")) {
                        result.add("ComputerFileJSTOR");
                        break;
                    }
                    else if (localCallNumberSubfieldA.contains("videogame")) {
                        result.add("VideoGame");
                        break;
                    }
                } else if (titleSubfieldH != null && titleSubfieldH.contains("[electronic resource]")) {
                    result.add("ElectronicResource");
                    break;
                }
                result.add("ComputerFile");
                break;

            case 'O': // Kit
                if (localCallNumberSubfieldA != null && localCallNumberSubfieldA.contains("musical instrument")) {
                    result.add("MusicalInstrument");
                    break;
                }
                result.add("Kit");
                break;

            case 'P':
                result.add("MixedMaterials");
                break;

            case 'R':
                result.add("ThreeDimensionalObject");
                break;

            case 'T':
                result.add("ManuscriptLanguageMaterial");
                break;
            }

        // Fallback default catch-all
        if (result.isEmpty()) {
            result.add("Unknown");
        }
        return result;
    }
}
