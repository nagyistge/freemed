/*
 * $Id$
 *
 * Authors:
 *      Jeff Buchbinder <jeff@freemedsoftware.org>
 *
 * FreeMED Electronic Medical Record and Practice Management System
 * Copyright (C) 1999-2008 FreeMED Software Foundation
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 */

package org.freemedsoftware.gwt.client.widget;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

import com.google.gwt.user.client.ui.HasHorizontalAlignment;
import com.google.gwt.user.client.ui.HasVerticalAlignment;
import com.thapar.gwt.user.ui.client.widget.SortableTable;

public class CustomSortableTable extends SortableTable {

	public class Column {
		protected String heading;

		protected String hashMapping;

		public Column() {
		}

		public Column(String newHeading, String newHashMapping) {
			setHeading(newHeading);
			setHashMapping(newHashMapping);
		}

		public String getHashMapping() {
			return hashMapping;
		}

		public String getHeading() {
			return heading;
		}

		public void setHashMapping(String newHashMapping) {
			hashMapping = newHashMapping;
		}

		public void setHeading(String newHeading) {
			heading = newHeading;
		}
	}

	protected Column[] columns = new Column[] {};

	protected String indexName = new String("id");;

	/**
	 * @gwt.typeArgs <java.lang.String,java.lang.String>
	 */
	protected HashMap indexMap;

	protected Integer maximumRows = new Integer(20);

	/**
	 * @gwt.typeArgs <java.lang.String,java.lang.String>
	 */
	protected HashMap[] data;

	public CustomSortableTable() {
		super();
		setStyleName("sortableTable");
		RowFormatter rowFormatter = getRowFormatter();
		rowFormatter.setStyleName(0, "tableHeader");
	}

	/**
	 * Add an additional column definition.
	 * 
	 * @param col
	 */
	public void addColumn(Column col) {
		int currentCols = 0;
		try {
			currentCols = columns.length;
		} catch (Exception e) {

		}
		this.addColumnHeader(col.getHeading(), currentCols);

		/**
		 * @gwt.typeArgs <Column>
		 */
		Set sA = new HashSet();
		for (int iter = 0; iter < currentCols; iter++) {
			sA.add(columns[iter]);
		}
		sA.add(col);
		columns = (Column[]) sA.toArray(new Column[0]);
	}

	/**
	 * Add an additional column definition.
	 * 
	 * @param col
	 */
	public void addColumn(String headerName, String hashMapping) {
		addColumn(new Column(headerName, hashMapping));
	}

	/**
	 * ` Format table with boiler plate.
	 * 
	 * @param columnCount
	 *            Number of columns present.
	 */
	public void formatTable(int rowCount) {
		{
			CellFormatter cellFormatter = getCellFormatter();
			for (int colIndex = 0; colIndex < columns.length; colIndex++) {
				cellFormatter.setStyleName(0, colIndex, "headerStyle");
				cellFormatter.setAlignment(0, colIndex,
						HasHorizontalAlignment.ALIGN_CENTER,
						HasVerticalAlignment.ALIGN_MIDDLE);
			}
		}

		// Format all the data, if it exists
		try {
			if (rowCount > 0) {
				RowFormatter rowFormatter = getRowFormatter();
				CellFormatter cellFormatter = getCellFormatter();
				for (int rowIndex = 1; rowIndex <= rowCount; rowIndex++) {
					// Alternating rows
					if (rowIndex % 2 == 0) {
						rowFormatter.setStyleName(rowIndex, "customRowStyle");
					} else {
						rowFormatter.setStyleName(rowIndex, "tableRow");
					}
					// Set column alignments and fonts
					for (int colIndex = 0; colIndex < columns.length; colIndex++) {
						cellFormatter.setStyleName(rowIndex, colIndex,
								"customFont");
						cellFormatter.setAlignment(rowIndex, colIndex,
								HasHorizontalAlignment.ALIGN_LEFT,
								HasVerticalAlignment.ALIGN_MIDDLE);
					}
				}
			}
		} catch (Exception e) {

		}
	}

	/**
	 * Resolve value of row based on the physical row number on the actual view.
	 * Meant to be used for things like TableListener.
	 * 
	 * @param row
	 * @return
	 */
	public String getValueByRow(int row) {
		return (String) indexMap.get((String) new Integer(row).toString());
	}

	/**
	 * @gwt.typeArgs newData <java.lang.String,java.lang.String>
	 * @param newData
	 */
	public void loadData(HashMap[] newData) {
		data = newData;
		int rows = (data.length < maximumRows.intValue()) ? data.length
				: maximumRows.intValue();
		for (int iter = 0; iter < rows; iter++) {
			// Set the value in the index map so clicks can be converted
			indexMap.put(new Integer(iter++).toString(), data[iter]
					.get(indexName));
			for (int jter = 0; jter < columns.length; jter++) {
				// Populate the column
				setText(iter++, jter, (String) data[iter]
						.get((String) columns[jter].getHashMapping()));
			}
		}
		formatTable(rows);
	}

	public void setIndexName(String newIndexName) {
		indexName = newIndexName;
	}

	public void setMaximumRows(Integer max) {
		maximumRows = max;
	}

}
