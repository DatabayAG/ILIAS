il.Util.addOnLoad(
  () => {
    const AC_DATASOURCE = 'ilias.php?baseClass=ilSearchControllerGUI&cmd=autoComplete';

    // we must bind the blur event before the autocomplete item is added
    document.getElementById('main_menu_search').addEventListener(
      'blur',
      (e) => { e.stopImmediatePropagation(); },
    );

    $('#main_menu_search').autocomplete({
      source: `${AC_DATASOURCE}&search_type=4`,
      appendTo: '#mm_search_menu_ac',
      open(event, ui) {
        $('.ui-autocomplete').position({
          my: 'left top',
          at: 'left top',
          of: $('#mm_search_menu_ac'),
        });
      },
      minLength: 3,
    });

    $("#ilMMSearchMenu input[type='radio']").change(() => {
      /* close current search */
      $('#main_menu_search').autocomplete('close');
      $('#main_menu_search').autocomplete('enable');

      /* append search type */

      const orig_datasource = AC_DATASOURCE;
      const checked_input = $('input[name=root_id]:checked', '#mm_search_form');
      const type_val = checked_input.val();

      /* disable autocomplete for search at current position */
      if (checked_input[0].id === 'ilmmsc') {
        $('#main_menu_search').autocomplete('disable');
        return;
      }

      $('#main_menu_search').autocomplete(
        'option',
        {
          source: `${orig_datasource}&search_type=${type_val}`,
        },
      );

      /* start new search */
      $('#main_menu_search').autocomplete('search');
    });
  },
);
