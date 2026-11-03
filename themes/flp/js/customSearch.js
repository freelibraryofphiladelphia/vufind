    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            // pageLanguage: 'en',
            includedLanguages: 'ar,en,es,fr,hi,ht,it,km,ko,pl,pt,ru,sq,vi,zh-CN',
            layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL,
            autoDisplay: false,
            gaTrack: true,
            gaId: 'UA-9995830-1'
        }, 'google_translate_element');
    }

    function translatePage(lang) {
        var selectElement = document.querySelector('.goog-te-combo');
        selectElement.value = lang;
        console.log(lang)
        console.log(selectElement.value)
        if (selectElement) {
            selectElement.dispatchEvent(new Event('change'));
        }
    }

    // Apply an ID to the Google Translate Select HTML tag so that a label can be added
    function addIdToGoogleTranslate() {
        const combo = document.querySelector(".goog-te-combo");
        if (combo) {
            combo.id = "gt-select-box";
            return;
        }

        setTimeout(addIdToGoogleTranslate, 200);
    }

    addIdToGoogleTranslate();

    // Remove the Google Translate Vote form to avoid WAVE errors
    function removeGoogleVotingPane() {
        const pane = document.getElementById("goog-gt-votingd-nonePane");
        if (pane) {
            pane.remove();
            return;
        }
        setTimeout(removeGoogleVotingPane, 200);
    }

    removeGoogleVotingPane();

    // $(function() {
    //     $('#header .collapse').on('show.bs.collapse', function (e) {
    //         var actives = $('#header').find('.in, .collapsing');
    //         actives.each(function (index, element) {
    //             $(element).collapse('hide');
    //         });
    //     });

    //     $('#google_translate_element').on('click', function(e) {
    //         e.preventDefault();
    //     });
    // });

    // Search Feature Toggle
    function switchSearch() {

        const searchWeb = document.getElementById("searchme-website");
        const searchCat = document.getElementById("searchme-catalog");
        const advancedSearch = document.getElementById("advanced-search");
        const catSearchFilters = document.getElementById("searchboxHandlers");
        // let searchSubmitText = document.getElementById("submitSearch").innerText;
        // let searchPlaceholder = document.getElementById("searchForm_lookfor").placeholder;

        const searchInput = document.getElementById("searchForm_lookfor");

        searchWeb.addEventListener("change", () => {
            const webSearch = searchWeb.checked;

            searchInput.placeholder = webSearch
                ? "Search for anything on our website..."
                : "Search for anything on our catalog...";

            advancedSearch.classList.toggle("d-none", webSearch);
            catSearchFilters.classList.toggle("d-none", webSearch);
        });

        // searchCat.addEventListener("click", () => {
        //     if (searchCat.checked == true) {
        //         document.getElementById("searchForm_lookfor").placeholder = "Search for anything on our catalog....";
        //         advancedSearch.classList.remove("d-none");
        //         catSearchFilters.classList.remove("d-none");
        //     }
        //     return;
        // });
    };

    switchSearch();
