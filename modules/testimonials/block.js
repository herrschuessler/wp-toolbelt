"use strict";

(function () {
  var registerBlockType = wp.blocks.registerBlockType;
  var createElement = wp.element.createElement;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var _wp$components = wp.components,
      RangeControl = _wp$components.RangeControl,
      RadioControl = _wp$components.RadioControl,
      PanelBody = _wp$components.PanelBody;
  var ServerSideRender = wp.serverSideRender;
  var __ = wp.i18n.__;
  registerBlockType('toolbelt/testimonials', {
    title: __('Testimonials', 'wp-toolbelt'),
    icon: 'testimonial',
    description: __('Display a grid of Toolbelt Testimonials.', 'wp-toolbelt'),
    keywords: [__('toolbelt', 'wp-toolbelt')],
    category: 'wp-toolbelt',
    supports: {
      align: ['full', 'wide']
    },
    attributes: {
      rows: {
        "default": 2
      },
      columns: {
        "default": 2
      },
      orderby: {
        "default": 'date'
      }
    },
    edit: function edit(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes; // Function to update the number of rows.

      var changeRows = function changeRows(rows) {
        setAttributes({
          rows: rows
        });
      }; // Function to update the number of columns.


      var changeColumns = function changeColumns(columns) {
        setAttributes({
          columns: columns
        });
      }; // Function to update the testimonial order.


      var changeOrderby = function changeOrderby(orderby) {
        setAttributes({
          orderby: orderby
        });
      };

      return [createElement(ServerSideRender, {
        block: "toolbelt/testimonials",
        attributes: attributes
      }), createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Layout', 'wp-toolbelt'),
        initialOpen: true
      }, createElement(RangeControl, {
        value: attributes.rows,
        label: __('Rows', 'wp-toolbelt'),
        onChange: changeRows,
        min: 1,
        max: 10
      }), createElement(RangeControl, {
        value: attributes.columns,
        label: __('Columns', 'wp-toolbelt'),
        onChange: changeColumns,
        min: 1,
        max: 4
      }), createElement(RadioControl, {
        selected: attributes.orderby,
        label: __('Order by', 'wp-toolbelt'),
        onChange: changeOrderby,
        options: [{
          value: 'date',
          label: __('date', 'wp-toolbelt')
        }, {
          value: 'rand',
          label: __('random', 'wp-toolbelt')
        }]
      })))];
    },
    save: function save() {
      return null;
    }
  });
})();